<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Gateway\RequestBuilder\Common;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Item;
use Tilta\Payment\Helper\AmountHelper;
use Tilta\Sdk\Model\Order\LineItem;

class LineItemsBuilder
{
    private const DEFAULT_CURRENCY = 'EUR';

    /**
     * @param Order $order
     * @return LineItem[]
     */
    public function buildForOrder(OrderInterface $order): array
    {
        $lineItems = [];

        /** @var Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $lineItems = array_merge($lineItems, $this->createItems($item));
        }

        foreach ($lineItems as $lineItem) {
            $lineItem->setCurrency($order->getBaseCurrencyCode() ?: self::DEFAULT_CURRENCY);
        }

        return $lineItems;
    }

    /**
     * @return LineItem[]
     */
    public function buildForCreditMemo(CreditmemoInterface $creditMemo): array
    {
        $lineItems = [];

        /** @var Creditmemo\Item $item */
        foreach ($creditMemo->getItems() as $item) {
            $lineItems = array_merge($lineItems, $this->createItems($item));
        }

        foreach ($lineItems as $lineItem) {
            $lineItem->setCurrency($creditMemo->getBaseCurrencyCode() ?: self::DEFAULT_CURRENCY);
        }

        return $lineItems;
    }

    /**
     * @param Invoice $invoice
     * @return LineItem[]
     */
    public function buildForInvoice(InvoiceInterface $invoice): array
    {
        $lineItems = [];

        /** @var Invoice\Item $item */
        foreach ($invoice->getAllItems() as $item) {
            $lineItems = array_merge($lineItems, $this->createItems($item));
        }

        foreach ($lineItems as $lineItem) {
            $lineItem->setCurrency($invoice->getBaseCurrencyCode() ?: self::DEFAULT_CURRENCY);
        }

        return $lineItems;
    }

    /**
     * @param Order\Item|Invoice\Item|Creditmemo\Item $item
     * @return LineItem[]
     */
    private function createItems(OrderItemInterface|InvoiceItemInterface|CreditmemoItemInterface $item): array
    {
        $qty = $item instanceof OrderItemInterface ? $item->getQtyOrdered() : $item->getQty();

        $lineItem = (new LineItem())
            ->setName((string) ($item->getName() ?: $item->getSku()))
            ->setPrice(AmountHelper::toSdk((float) $item->getBasePriceInclTax()))
            ->setQuantity((int) $qty)
            ->setCategory(''); // TODO should this also be send?

        $lineItem = $this->prepareConfigurableData($item, $lineItem);
        if (!$lineItem instanceof LineItem) {
            return [];
        }

        $lineItems = $this->prepareBundleData($item, $lineItem);
        if (is_array($lineItems)) {
            return $lineItems;
        }

        return [$lineItem];
    }

    /**
     * @param Order\Item|Invoice\Item|Creditmemo\Item $item
     * @return LineItem[]
     */
    private function prepareBundleData(OrderItemInterface|InvoiceItemInterface|CreditmemoItemInterface $item, LineItem $lineItem): ?array
    {
        if ($item instanceof OrderItemInterface && $item->getProductType() === 'bundle') {
            $bundleLineItems = [];
            foreach ($item->getChildrenItems() as $child) {
                $bundleLineItems = array_merge($bundleLineItems, $this->createItems($child));
            }

            return $bundleLineItems;
        }

        if (($item instanceof Invoice\Item || $item instanceof Creditmemo\Item)
            && $item->getOrderItem()->getProductType() === 'bundle'
        ) {
            // bundle products has been already stored as separated positions
            return [];
        }

        return null;
    }

    private function prepareConfigurableData(OrderItemInterface|InvoiceItemInterface|CreditmemoItemInterface $item, LineItem $lineItem): ?LineItem
    {
        if (($item instanceof Invoice\Item || $item instanceof Creditmemo\Item)
            && $item->getOrderItem()->getParentItem()?->getProductType() === 'configurable'
        ) {
            // the simple product within the invoice/credit-memo does not have a price.
            return null;
        }

        if ($item instanceof Item) {
            $options = $item->getProductOptions();
            if (is_array($options) && isset($options['simple_name'])) {
                $lineItem->setName($options['simple_name']);
            }
        }

        if ($item instanceof Invoice\Item || $item instanceof Creditmemo\Item) {
            $orderItem = $item->getOrderItem();
            $options = $orderItem->getProductOptions();
            if (is_array($options) && isset($options['simple_name'])) {
                $lineItem->setName($options['simple_name']);
            }
        }

        return $lineItem;
    }
}
