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
use Magento\Sales\Api\Data\OrderInterface;
use Tilta\Payment\Helper\AmountHelper;
use Tilta\Sdk\Model\Order\LineItem;

class LineItemsBuilder
{
    /**
     * @return LineItem[]
     */
    public function buildForOrder(OrderInterface $order): array
    {
        $lineItems = [];

        foreach ($order->getItems() as $item) {
            $lineItems[] = (new LineItem())
                ->setName($item->getName() ?: $item->getSku())
                ->setQuantity((int) $item->getQtyOrdered())
                ->setPrice(AmountHelper::toSdk((float) $item->getPrice()))
                ->setCurrency($order->getBaseCurrencyCode() ?: 'EUR')
                ->setCategory('') // TODO should this also be send?
            ;
        }

        return $lineItems;
    }

    /**
     * @return LineItem[]
     */
    public function buildForCreditMemo(CreditmemoInterface $creditMemo): array
    {
        $lineItems = [];

        foreach ($creditMemo->getItems() as $item) {
            $lineItems[] = (new LineItem())
                ->setName((string) ($item->getName() ?: $item->getSku()))
                ->setQuantity((int) $item->getQty())
                ->setPrice(AmountHelper::toSdk((float) $item->getPrice()))
                ->setCurrency($creditMemo->getBaseCurrencyCode() ?: 'EUR')
                ->setCategory('') // TODO should this also be send?
            ;
        }

        return $lineItems;
    }
}
