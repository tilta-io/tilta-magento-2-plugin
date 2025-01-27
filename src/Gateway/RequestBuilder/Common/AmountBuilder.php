<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Gateway\RequestBuilder\Common;

use InvalidArgumentException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Tilta\Payment\Helper\AmountHelper;
use Tilta\Sdk\Model\Amount;

class AmountBuilder
{
    public function createForOrder(OrderInterface $order): Amount
    {
        return (new Amount())
            ->setCurrency($order->getBaseCurrencyCode() ?: 'EUR')
            ->setGross(AmountHelper::toSdk($order->getBaseGrandTotal()))
            ->setNet(AmountHelper::toSdk($order->getBaseGrandTotal() - $order->getBasetaxAmount()));
    }

    public function createForCart(CartInterface $cart): Amount
    {
        if (!$cart instanceof Quote) {
            throw new InvalidArgumentException('Cart must be an instance of Quote');
        }

        $taxTotal = $cart->getTotals()['tax'] ?? null;

        return (new Amount())
            ->setCurrency($cart->getBaseCurrencyCode() ?: 'EUR')
            ->setGross(AmountHelper::toSdk((float) $cart->getBaseGrandTotal()))
            ->setNet(AmountHelper::toSdk($taxTotal ? ((float) $cart->getBaseGrandTotal() - (float) $taxTotal->getValue() / (float) $cart->getBaseToQuoteRate()) : (float) $cart->getBaseGrandTotal()));
    }

    public function createForInvoice(InvoiceInterface $invoice): Amount
    {
        return (new Amount())
            ->setCurrency($invoice->getBaseCurrencyCode() ?: 'EUR')
            ->setGross(AmountHelper::toSdk((float) $invoice->getBaseGrandTotal()))
            ->setNet(AmountHelper::toSdk(((float) $invoice->getBaseGrandTotal()) - ((float) $invoice->getBasetaxAmount())));
    }

    public function createForCreditMemo(CreditmemoInterface $creditMemo): Amount
    {
        return (new Amount())
            ->setCurrency($creditMemo->getBaseCurrencyCode() ?: 'EUR')
            ->setGross(AmountHelper::toSdk((float) $creditMemo->getBaseGrandTotal()))
            ->setNet(AmountHelper::toSdk(((float) $creditMemo->getBaseGrandTotal()) - ((float) $creditMemo->getBasetaxAmount())));
    }
}
