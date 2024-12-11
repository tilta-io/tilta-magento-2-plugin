<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Gateway\RequestBuilder\Common;

use Magento\Sales\Api\Data\OrderAddressInterface;
use Tilta\Sdk\Model\Address;
use Tilta\Sdk\Util\AddressHelper;

class AddressBuilder
{
    public function buildForOrderAddress(OrderAddressInterface $orderAddress): Address
    {
        $street0 = $orderAddress->getStreet()[0] ?? null;

        return (new Address())
            ->setStreet(is_string($street0) ? AddressHelper::getStreetName($street0) ?: '' : '')
            ->setHouseNumber(is_string($street0) ? AddressHelper::getHouseNumber($street0) ?: '' : '')
            ->setPostcode($orderAddress->getPostcode() ?: '')
            ->setCity($orderAddress->getCity())
            ->setCountry($orderAddress->getCountryId() ?: '')
            ->setAdditional($this->mergeAdditionalAddressLines($orderAddress));
    }

    private function mergeAdditionalAddressLines(OrderAddressInterface $orderAddress): string
    {
        $lines = $orderAddress->getStreet() ?: [];
        unset($lines[0]);

        $lines = array_filter($lines, static fn ($line): bool => !empty(trim((string) $line)));

        return implode("\n", $lines);
    }
}
