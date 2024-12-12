<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Model;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Block\Widget\Telephone;
use Magento\Framework\Exception\LocalizedException;
use Tilta\Payment\Api\CreditFacilityManagementInterface;
use Tilta\Payment\Api\Data\CreditFacilityRequest\RequestInterface;
use Tilta\Payment\Exception\MissingBuyerInformationException;
use Tilta\Payment\Service\BuyerService;
use Tilta\Payment\Service\FacilityService;
use Tilta\Sdk\Exception\TiltaException;

class CreditFacilityManagement implements CreditFacilityManagementInterface
{
    public function __construct(
        private readonly BuyerService $buyerService,
        private readonly FacilityService $facilityService,
        private readonly AddressRepositoryInterface $addressRepository,
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function requestCreditFacility(int $customerId, int $customerAddressId, RequestInterface $additionalData): void
    {
        $customerAddress = $this->addressRepository->getById($customerAddressId);
        if ((int) $customerAddress->getCustomerId() !== $customerId) {
            throw new LocalizedException(__('Unknown customer address.'));
        }

        $this->buyerService->updateCustomerAddressData($customerAddress, [
            Telephone::ATTRIBUTE_CODE => $additionalData->getTelephone(),
            CustomerAddressBuyer::LEGAL_FORM => $additionalData->getLegalForm(),
            CustomerAddressBuyer::INCORPORATED_AT => $additionalData->getIncorporationDate(),
        ]);

        try {
            $this->facilityService->createFacilityForBuyerIfNotExist($customerAddress);
        } catch (MissingBuyerInformationException $missingBuyerInformationException) {
            throw new LocalizedException(__(implode(' ', $missingBuyerInformationException->getErrorMessages())), $missingBuyerInformationException, $missingBuyerInformationException->getCode());
        } catch (TiltaException $exception) {
            throw new LocalizedException(__($exception->getMessage()), $exception, $exception->getCode());
        }
    }
}
