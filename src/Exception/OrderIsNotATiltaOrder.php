<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Exception;

use Magento\Framework\Exception\LocalizedException;

class OrderIsNotATiltaOrder extends LocalizedException
{
    public function __construct(int $id)
    {
        parent::__construct(__('Order with id %1 is not a Tilta order.', $id));
    }
}
