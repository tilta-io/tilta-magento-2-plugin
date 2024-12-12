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
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use Throwable;
use Tilta\Payment\Api\CheckoutPaymentTermsInterface;
use Tilta\Payment\Api\Data\CheckoutPaymentTermInterface;
use Tilta\Payment\Api\Data\CheckoutPaymentTermsResponseInterface;
use Tilta\Payment\Helper\AmountHelper;
use Tilta\Payment\Service\FacilityService;
use Tilta\Payment\Service\PaymentTermsService;
use Tilta\Sdk\Exception\GatewayException\Facility\FacilityExceededException;
use Tilta\Sdk\Model\Response\PaymentTerm\GetPaymentTermsResponseModel;

class CheckoutPaymentTerms implements CheckoutPaymentTermsInterface
{
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly ObjectManagerInterface $objectManager,
        private readonly PaymentTermsService $paymentTermsService,
        private readonly FacilityService $facilityService,
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getPaymentTermsForCart(int $customerAddressId, int $cartId): CheckoutPaymentTermsResponseInterface
    {
        /** @var Quote $quote */
        $quote = $this->cartRepository->get($cartId);

        /** @var CheckoutPaymentTermsResponseInterface $response */
        $response = $this->objectManager->create(CheckoutPaymentTermsResponseInterface::class);

        try {
            $paymentTerms = $this->paymentTermsService->getPaymentTermsForQuote($quote, $customerAddressId);
        } catch (FacilityExceededException) {
            $customerAddress = $this->addressRepository->getById($customerAddressId);
            $facility = $this->facilityService->getFacility($customerAddress);
            if ($facility && $facility->getTotalAmount() < AmountHelper::toSdk((float) $quote->getBaseGrandTotal())) {
                // facility is not exceeded, the facility is too low to be get used for this order.
                $response->setErrorMessage((string) __('Your credit limit does not cover the total of this order. Please review and adjust your order or payment method accordingly. If you have any questions or require assistance, please contact our customer service.'));
            } else {
                $response->setErrorMessage((string) __('Your credit limit is currently reached. Please settle any outstanding invoices before placing another order on account. If you have any questions or need assistance, our customer service is here to help.'));
            }

            return $response;
        } catch (Throwable $exception) {
            $response->setErrorMessage((string) __('Unfortunately, you cannot use this payment method. Please contact customer service.'));
            $this->logger->error('Tilta Payments: Error during fetching actual facility for buyer to validate if the facility is to low for the order. ' . $exception->getMessage(), [
                'quote_id' => $cartId,
            ]);

            return $response;
        }

        $responseTerms = [];
        foreach ($paymentTerms?->getPaymentTerms() ?: [] as $paymentTerm) {
            /** @var CheckoutPaymentTermInterface $term */
            $term = $this->objectManager->create(CheckoutPaymentTermInterface::class);
            $term->setPaymentMethod($paymentTerm->getPaymentMethod());
            $term->setPaymentTerm($paymentTerm->getPaymentTerm());
            $term->setName($paymentTerm->getName());
            $term->setDueDate($paymentTerm->getDueDate()->format('Y-m-d'));

            $responseTerms[] = $term;
        }

        $response->setPaymentTerms($responseTerms);
        $response->setAllowCreateFacility(!$paymentTerms instanceof GetPaymentTermsResponseModel);

        return $response;
    }
}
