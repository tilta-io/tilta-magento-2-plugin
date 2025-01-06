<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Model;

use DateTime;
use InvalidArgumentException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManagerInterface;
use Tilta\Payment\Api\CustomerAddressBuyerRepositoryInterface;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;
use Tilta\Payment\Model\ResourceModel\CustomerAddressBuyer;

class CustomerAddressBuyerRepository implements CustomerAddressBuyerRepositoryInterface
{
    public function __construct(
        private readonly CustomerAddressBuyer $customerAddressBuyerResource,
        private readonly ObjectManagerInterface $objectManager
    ) {
    }

    public function getByCustomerAddressId(int $customerAddressId): CustomerAddressBuyerInterface
    {
        /** @var CustomerAddressBuyerInterface $model */
        $model = $this->objectManager->create(CustomerAddressBuyerInterface::class);

        if (!$model instanceof AbstractModel) {
            throw new InvalidArgumentException('implementation of CustomerAddressBuyerInterface must be a AbstractModel');
        }

        $this->customerAddressBuyerResource->load($model, $customerAddressId, CustomerAddressBuyerInterface::CUSTOMER_ADDRESS_ID);

        if (empty($model->getData(CustomerAddressBuyerInterface::CUSTOMER_ADDRESS_ID))) {
            throw new NoSuchEntityException(__('Tilta Customer address data not found'));
        }

        return $model;
    }

    public function save(CustomerAddressBuyerInterface $customerAddressBuyer): void
    {
        if (!$customerAddressBuyer instanceof AbstractModel) {
            throw new InvalidArgumentException('$customerAddressBuyer must be an instance of AbstractModel');
        }

        if ($customerAddressBuyer->isObjectNew()) {
            $customerAddressBuyer->setCreatedAt((new DateTime())->format($customerAddressBuyer::DATETIME_FORMAT));
        } else {
            $customerAddressBuyer->setUpdatedAt((new DateTime())->format($customerAddressBuyer::DATETIME_FORMAT));
        }

        $this->customerAddressBuyerResource->save($customerAddressBuyer);
    }

    public function delete(CustomerAddressBuyerInterface $customerAddressBuyer): void
    {
        if (!$customerAddressBuyer instanceof AbstractModel) {
            throw new InvalidArgumentException('$customerAddressBuyer must be an instance of AbstractModel');
        }

        $this->customerAddressBuyerResource->delete($customerAddressBuyer);
    }
}
