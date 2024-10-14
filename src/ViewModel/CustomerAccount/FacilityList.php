<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\ViewModel\CustomerAccount;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Block\Address\Renderer\RendererInterface;
use Magento\Customer\Helper\Address;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use RuntimeException;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;
use Tilta\Payment\Helper\AmountHelper;
use Tilta\Payment\Service\FacilityService;
use Tilta\Sdk\Exception\GatewayException\Facility\NoActiveFacilityFoundException;
use Tilta\Sdk\Exception\GatewayException\NotFoundException\BuyerNotFoundException;
use Tilta\Sdk\Model\Response\Facility\GetFacilityResponseModel;

class FacilityList implements ArgumentInterface
{
    public function __construct(
        private readonly Session $customerSession,
        private readonly Address $addressHelper,
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly Mapper $addressMapper,
        private readonly FacilityService $facilityService,
        private readonly PriceCurrencyInterface $priceCurrency,
    ) {
    }

    /**
     * @return AddressInterface[]
     */
    public function getList(): array
    {
        $this->searchCriteriaBuilder->addFilter('parent_id', $this->customerSession->getCustomerId());
        $this->searchCriteriaBuilder->addFilter(AddressInterface::COMPANY, null, 'neq');

        $list = $this->addressRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        return array_filter($list, static fn (AddressInterface $address): bool => !empty($address->getCompany()));
    }

    public function formatAddress(AddressInterface $address): string
    {
        $renderer = $this->addressHelper->getFormatTypeRenderer('html');
        if (!$renderer instanceof RendererInterface) {
            throw new LocalizedException(__('Can not render address'));
        }

        return (string) $renderer->renderArray($this->addressMapper->toFlatArray($address));
    }

    public function getFacilityUsage(AddressInterface $addressEntity): ?DataObject
    {
        $extension = $addressEntity->getExtensionAttributes();
        if (!$extension) {
            throw new RuntimeException('extension of address has not been loaded');
        }

        $buyerData = $extension->getTiltaBuyer();
        if (!$buyerData instanceof CustomerAddressBuyerInterface) {
            return null;
        }

        try {
            $facility = $this->facilityService->getFacility($addressEntity);
        } catch (NoActiveFacilityFoundException|BuyerNotFoundException) {
            return null;
        }

        if (!$facility instanceof GetFacilityResponseModel) {
            return null;
        }

        $currency = $facility->getCurrency();

        return new DataObject([
            'total_amount' => $this->priceCurrency->convertAndFormat(AmountHelper::fromSdk($facility->getTotalAmount()), false, currency: $currency),
            'available_amount' => $this->priceCurrency->convertAndFormat(AmountHelper::fromSdk($facility->getAvailableAmount()), false, currency: $currency),
            'used_amount' => $this->priceCurrency->convertAndFormat(AmountHelper::fromSdk($facility->getUsedAmount() + $facility->getPendingOrdersAmount()), false, currency: $currency),
            'usage_percentage' => ($facility->getAvailableAmount() / $facility->getTotalAmount()) * 100,
        ]);
    }
}
