<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class OnCaptureInvoice implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $invoice = $observer->getData('invoice');

        /** @var DataObject $payment */
        $payment = $observer->getData('payment');
        $payment->setData('_tilta_invoice_to_process', $invoice);
    }
}
