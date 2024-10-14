<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Exception;

use Magento\Framework\Exception\InputException;

class CountryChangeIsNotAllowedException extends InputException
{
    public function __construct(
        private readonly array $addressIds
    ) {
        parent::__construct(__('Changing the country on a existing customer-address is not allowed, if the address does have a credit facility.'));
    }

    /**
     * @return int[]
     */
    public function getAddressIds(): array
    {
        return $this->addressIds;
    }
}
