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
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Payment;
use Magento\SalesSequence\Model\Manager;
use Tilta\Payment\Gateway\RequestBuilder\Common\AddressBuilder;
use Tilta\Payment\Gateway\RequestBuilder\Common\AmountBuilder;
use Tilta\Payment\Gateway\RequestBuilder\Common\LineItemsBuilder;
use Tilta\Sdk\Model\Request\CreditNote\CreateCreditNoteRequestModel;

class RefundInvoiceRequestBuilder implements BuilderInterface
{
    public function __construct(
        private readonly AmountBuilder $amountBuilder,
        private readonly AddressBuilder $addressBuilder,
        private readonly LineItemsBuilder $lineItemsBuilder,
        private readonly Manager $sequenceManager,
    ) {
    }

    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();
        if (!$payment instanceof Payment) {
            throw new LocalizedException(__('Can not build refund data'));
        }

        $creditMemo = $payment->getCreditMemo();
        if (!$creditMemo instanceof Creditmemo) {
            throw new LocalizedException(__('Can not build refund data'));
        }

        if (empty($creditMemo->getIncrementId())) {
            $creditMemo->setIncrementId(
                $this->sequenceManager->getSequence(
                    $creditMemo->getEntityType(),
                    (int) $creditMemo->getStoreId(),
                )->getNextValue()
            );
        }

        $buyerExternalId = $payment->getAdditionalInformation('tilta_buyer_external_id');

        if (empty($buyerExternalId)) {
            throw new LocalizedException(__('Buyer External ID has not been set.'));
        }

        $requestModel = (new CreateCreditNoteRequestModel())
            ->setBuyerExternalId($buyerExternalId)
            ->setOrderExternalIds([$order->getOrderIncrementId()])
            ->setCreditNoteExternalId($creditMemo->getIncrementId())
            ->setAmount($this->amountBuilder->createForCreditMemo($creditMemo))
            ->setInvoicedAt(new DateTime())
            ->setBillingAddress($this->addressBuilder->buildForOrderAddress($creditMemo->getBillingAddress()))
            ->setLineItems($this->lineItemsBuilder->buildForCreditMemo($creditMemo));

        return [
            'request_model' => $requestModel,
        ];
    }
}
