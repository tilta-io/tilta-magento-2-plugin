<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Api;

use Tilta\Payment\Api\Data\CreditFacilityRequest\RequestInterface;

interface CreditFacilityManagementInterface
{
    /**
     * @param int $customerId
     * @param int $customerAddressId
     * @param \Tilta\Payment\Api\Data\CreditFacilityRequest\RequestInterface $additionalData
     * @return void
     */
    public function requestCreditFacility(int $customerId, int $customerAddressId, RequestInterface $additionalData): void;
}
