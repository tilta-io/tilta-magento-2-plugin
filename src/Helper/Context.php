<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Helper;

use Magento\Customer\Api\Data\AddressInterface;
use RuntimeException;

class Context
{
    private ?AddressInterface $customerAddress = null;

    public function setCurrentEditAddress(AddressInterface $address): void
    {
        $this->customerAddress = $address;
    }

    public function getCurrentEditAddress(): AddressInterface
    {
        if (!$this->customerAddress instanceof AddressInterface) {
            throw new RuntimeException('address not set in context');
        }

        return $this->customerAddress;
    }
}
