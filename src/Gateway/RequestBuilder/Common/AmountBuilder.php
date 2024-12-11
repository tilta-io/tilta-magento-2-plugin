<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Gateway\RequestBuilder\Common;

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
}
