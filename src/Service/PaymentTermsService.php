<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Tilta\Payment\Api\CustomerAddressBuyerRepositoryInterface;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;
use Tilta\Payment\Helper\AmountHelper;
use Tilta\Sdk\Exception\GatewayException\Facility\FacilityExceededException;
use Tilta\Sdk\Exception\GatewayException\Facility\NoActiveFacilityFoundException;
use Tilta\Sdk\Exception\GatewayException\NotFoundException\BuyerNotFoundException;
use Tilta\Sdk\Model\Amount;
use Tilta\Sdk\Model\Request\PaymentTerm\GetPaymentTermsRequestModel;
use Tilta\Sdk\Model\Response\PaymentTerm\GetPaymentTermsResponseModel;
use Tilta\Sdk\Service\Request\PaymentTerm\GetPaymentTermsRequest;

class PaymentTermsService
{
    public function __construct(
        private readonly RequestServiceFactory $requestServiceFactory,
        private readonly CustomerAddressBuyerRepositoryInterface $buyerRepository,
        private readonly ConfigService $configService,
        private readonly FacilityService $facilityService
    ) {
    }

    /**
     * @throws BuyerNotFoundException
     * @throws NoActiveFacilityFoundException
     * @throws FacilityExceededException
     */
    public function getPaymentTermsForQuote(CartInterface $cart, ?int $customerAddressId = null): ?GetPaymentTermsResponseModel
    {
        $billingAddressId = is_numeric($customerAddressId) ? $customerAddressId : $cart->getBillingAddress()?->getCustomerAddressId();
        if (empty($billingAddressId)) {
            return null;
        }

        try {
            $customerAddressBuyer = $this->buyerRepository->getByCustomerAddressId((int) $billingAddressId);
        } catch (NoSuchEntityException) {
            return null;
        }


        return $this->getPaymentTermsForCustomerAddressBuyer($customerAddressBuyer, $cart);
    }

    /**
     * @throws BuyerNotFoundException
     * @throws NoActiveFacilityFoundException
     * @throws FacilityExceededException
     */
    public function getPaymentTermsForCustomerAddressBuyer(CustomerAddressBuyerInterface $customerAddressBuyer, CartInterface $cart): ?GetPaymentTermsResponseModel
    {
        if (empty($customerAddressBuyer->getBuyerExternalId())) {
            return null;
        }

        if (!$cart instanceof Quote) {
            throw new LocalizedException(__('cart must be an instance of quote'));
        }

        $requestModel = (new GetPaymentTermsRequestModel())
            ->setMerchantExternalId($this->configService->getMerchantExternalId())
            ->setBuyerExternalId($customerAddressBuyer->getBuyerExternalId())
            ->setAmount(
                (new Amount())
                    ->setCurrency($cart->getBaseCurrencyCode() ?: 'EUR')
                    ->setGross(AmountHelper::toSdk((float) $cart->getBaseGrandTotal()))
                    ->setNet(AmountHelper::toSdk(((float) $cart->getBaseGrandTotal()) - 0)) // TODO use korrekt keys $cart->getTotals()
            );

        try {
            /** @var GetPaymentTermsRequest $service */
            $service = $this->requestServiceFactory->get(GetPaymentTermsRequest::class);
            $paymentTerms = $service->execute($requestModel);

            $this->facilityService->updateFacilityOnCustomerAddress($customerAddressBuyer, $paymentTerms->getFacility());

            return $paymentTerms;
        } catch (BuyerNotFoundException|NoActiveFacilityFoundException) {
            return null;
        }
    }
}
