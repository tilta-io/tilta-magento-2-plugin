<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Gateway\ResponseHandler;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use Tilta\Sdk\Model\AbstractModel;
use Tilta\Sdk\Model\Order;

class CreateOrderResponseHandler implements HandlerInterface
{
    public function handle(array $handlingSubject, AbstractModel|bool $response): void
    {
        if (!$response instanceof Order) {
            throw new LocalizedException(__('Unexpected response model. Expected %1, got %2.', Order::class, get_debug_type($response)));
        }

        $paymentDO = SubjectReader::readPayment($handlingSubject);

        if ($paymentDO->getPayment() instanceof Payment) {
            $paymentDO->getPayment()->setTransactionId($paymentDO->getOrder()->getOrderIncrementId());
            $paymentDO->getPayment()->setIsTransactionClosed(false);
            $paymentDO->getPayment()->setAdditionalInformation('tilta_buyer_external_id', $response->getBuyerExternalId());
        }
    }
}
