<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Observer;

use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Tilta\Payment\Exception\CountryChangeIsNotAllowedException;
use Tilta\Payment\Service\BuyerService;

class OnAddressSaveCountryValidation implements ObserverInterface
{
    public function __construct(
        private readonly BuyerService $buyerService
    ) {
    }

    public function execute(Observer $observer): void
    {
        $address = $observer->getData('data_object');
        if (!$address instanceof AbstractAddress || empty($address->getId())) {
            return;
        }

        if ($address->getOrigData()['country_id'] !== $address->getCountryId()
            && !$this->buyerService->canChangeCountry((int) $address->getId())
        ) {
            throw new CountryChangeIsNotAllowedException([$address->getId()]);
        }
    }
}
