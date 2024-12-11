<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Gateway\RequestBuilder;

use DateTime;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order;
use ReflectionProperty;
use Tilta\Payment\Api\CustomerAddressBuyerRepositoryInterface;
use Tilta\Payment\Gateway\RequestBuilder\Common\AddressBuilder;
use Tilta\Payment\Gateway\RequestBuilder\Common\LineItemsBuilder;
use Tilta\Payment\Helper\AmountHelper;
use Tilta\Payment\Observer\TiltaPaymentDataAssignAdditionalData;
use Tilta\Payment\Service\ConfigService;
use Tilta\Sdk\Model\Amount;
use Tilta\Sdk\Model\Request\Order\CreateOrderRequestModel;

class CreateOrderRequestBuilder implements BuilderInterface
{
    public function __construct(
        private readonly LineItemsBuilder $lineItemsBuilder,
        private readonly AddressBuilder $addressBuilder,
        private readonly ConfigService $configService,
        private readonly CustomerAddressBuyerRepositoryInterface $buyerRepository,
        private readonly SubjectReader $subjectReader
    ) {
    }

    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        // TODO is there any better solution to get more details about the order?
        /** @var Order $order */
        $order = (new ReflectionProperty($order, 'order'))->getValue($order);

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress === null) {
            throw new LocalizedException(__('Billing address is required.'));
        }

        $buyer = $this->buyerRepository->getByCustomerAddressId((int) $billingAddress->getCustomerAddressId());

        $externalId = $buyer->getBuyerExternalId();
        if (empty($externalId)) {
            throw new LocalizedException(__('Buyer does not have valid facility.'));
        }

        $paymentTerm = $paymentDO->getPayment()->getAdditionalInformation(TiltaPaymentDataAssignAdditionalData::PAYMENT_TERM);
        $paymentTerm = is_string($paymentTerm) ? $paymentTerm : null;

        $paymentMethod = $paymentDO->getPayment()->getAdditionalInformation(TiltaPaymentDataAssignAdditionalData::PAYMENT_METHOD);
        $paymentMethod = is_string($paymentMethod) ? $paymentMethod : null;

        if (empty($paymentTerm) || empty($paymentMethod)) {
            throw new LocalizedException(__('Missing payment information.'));
        }

        $orderRequestModel =
            (new CreateOrderRequestModel())
                ->setMerchantExternalId($this->configService->getMerchantExternalId())
                ->setBuyerExternalId($externalId)
                ->setPaymentMethod($paymentMethod)
                ->setPaymentTerm($paymentTerm)
                ->setOrderedAt((new DateTime($order->getCreatedAt() ?? 'now')))
                ->setOrderExternalId($order->getIncrementId())
                ->setAmount(
                    (new Amount())
                        ->setCurrency($order->getOrderCurrencyCode() ?: 'EUR')
                        ->setGross(AmountHelper::toSdk($order->getBaseGrandTotal()))
                        ->setNet(AmountHelper::toSdk($order->getBaseGrandTotal() - $order->getBaseTaxAmount()))
                );

        if (($shippingAddress = $order->getShippingAddress()) instanceof OrderAddressInterface) {
            $orderRequestModel->setDeliveryAddress(
                $this->addressBuilder->buildForOrderAddress($shippingAddress)
            );
        }

        $orderRequestModel->setLineItems($this->lineItemsBuilder->buildForOrder($order));

        return [
            'request_model' => $orderRequestModel,
        ];
    }
}
