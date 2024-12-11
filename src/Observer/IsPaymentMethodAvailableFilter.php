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
use Magento\Payment\Model\Method\Adapter;
use Magento\Quote\Api\Data\CartInterface;
use Tilta\Payment\Model\Ui\ConfigProvider;

class IsPaymentMethodAvailableFilter implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        /** @var Adapter $method */
        $method = $observer->getData('method_instance');
        if ($method->getCode() !== ConfigProvider::METHOD_CODE) {
            return;
        }

        /** @var DataObject $result */
        $result = $observer->getData('result');

        /** @var CartInterface $quote */
        $quote = $observer->getData('quote');
        $billingAddress = $quote->getBillingAddress();
        if ($billingAddress === null) {
            $result->setData('is_available', false);
            return;
        }

        if (empty($billingAddress->getCustomerAddressId())) {
            $result->setData('is_available', false);
            return;
        }

        if (empty($billingAddress->getCompany())) {
            $result->setData('is_available', false);
            return;
        }
    }
}
