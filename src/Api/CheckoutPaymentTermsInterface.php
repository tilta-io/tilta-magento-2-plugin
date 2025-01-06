<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Api;

use Tilta\Payment\Api\Data\CheckoutPaymentTermsResponseInterface;

interface CheckoutPaymentTermsInterface
{
    /**
     * @param int $customerAddressId
     * @param int $cartId
     * @return \Tilta\Payment\Api\Data\CheckoutPaymentTermsResponseInterface
     */
    public function getPaymentTermsForCart(int $customerAddressId, int $cartId): CheckoutPaymentTermsResponseInterface;
}
