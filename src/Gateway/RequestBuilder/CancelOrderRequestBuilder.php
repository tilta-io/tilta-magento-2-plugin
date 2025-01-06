<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Gateway\RequestBuilder;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Tilta\Sdk\Model\Request\Order\CancelOrderRequestModel;

class CancelOrderRequestBuilder implements BuilderInterface
{
    public function build(array $buildSubject): array
    {
        $payment = SubjectReader::readPayment($buildSubject);

        return [
            'request_model' => new CancelOrderRequestModel($payment->getOrder()->getOrderIncrementId()),
        ];
    }
}
