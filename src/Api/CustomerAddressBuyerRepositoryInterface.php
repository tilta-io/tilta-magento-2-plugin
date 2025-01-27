<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;

interface CustomerAddressBuyerRepositoryInterface
{
    /**
     * @throws NoSuchEntityException
     */
    public function getByCustomerAddressId(int $customerAddressId): CustomerAddressBuyerInterface;

    public function save(CustomerAddressBuyerInterface $customerAddressBuyer): void;

    public function delete(CustomerAddressBuyerInterface $customerAddressBuyer): void;

    public function deleteById(int $customerAddressBuyerId): void;
}
