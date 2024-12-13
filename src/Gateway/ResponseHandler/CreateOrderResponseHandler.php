<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Gateway\ResponseHandler;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use Tilta\Sdk\Model\AbstractModel;

class CreateOrderResponseHandler implements HandlerInterface
{
    public function handle(array $handlingSubject, AbstractModel|bool $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        if ($paymentDO->getPayment() instanceof Payment) {
            $paymentDO->getPayment()->setTransactionId($paymentDO->getOrder()->getOrderIncrementId());
            $paymentDO->getPayment()->setIsTransactionClosed(false);
        }
    }
}
