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
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\SalesSequence\Model\Manager;
use ReflectionProperty;
use Tilta\Payment\Gateway\RequestBuilder\Common\AddressBuilder;
use Tilta\Payment\Gateway\RequestBuilder\Common\AmountBuilder;
use Tilta\Payment\Gateway\RequestBuilder\Common\LineItemsBuilder;
use Tilta\Sdk\Model\Request\Invoice\CreateInvoiceRequestModel;

class CreateInvoiceRequestBuilder implements BuilderInterface
{
    public function __construct(
        private readonly Manager $sequenceManager,
        private readonly AmountBuilder $amountBuilder,
        private readonly AddressBuilder $addressBuilder,
        private readonly LineItemsBuilder $lineItemsBuilder,
    ) {
    }

    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        // TODO is there any better solution to get more details about the order?
        /** @var Order $order */
        $order = (new ReflectionProperty($order, 'order'))->getValue($order);

        $invoice = $order->getInvoiceCollection()->getFirstItem();

        if (!$invoice instanceof Invoice) {
            throw new LocalizedException(__('Invoice was not found.'));
        }


        if (empty($invoice->getIncrementId())) {
            $invoice->setIncrementId(
                $this->sequenceManager->getSequence(
                    $invoice->getEntityType(),
                    (int) $invoice->getStoreId(),
                )->getNextValue()
            );
        }

        $requestModel = (new CreateInvoiceRequestModel())
            ->setOrderExternalIds([$order->getIncrementId()])
            ->setInvoiceNumber($invoice->getIncrementId())
            ->setInvoiceExternalId($order->getEntityId())
            ->setAmount($this->amountBuilder->createForInvoice($invoice))
            ->setInvoicedAt(new DateTime())
            ->setBillingAddress($this->addressBuilder->buildForOrderAddress($invoice->getBillingAddress()))
            ->setLineItems($this->lineItemsBuilder->buildForOrder($order));

        return [
            'request_model' => $requestModel,
        ];
    }
}
