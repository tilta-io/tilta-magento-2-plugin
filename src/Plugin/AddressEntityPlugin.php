<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Plugin;

use Magento\Customer\Api\Data\AddressExtensionInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Tilta\Payment\Api\CustomerAddressBuyerRepositoryInterface;

class AddressEntityPlugin
{
    public function __construct(
        private readonly CustomerAddressBuyerRepositoryInterface $repository,
        private readonly ExtensionAttributesFactory $extensionAttributesFactory,
    ) {
    }

    public function afterGetExtensionAttributes(AddressInterface $subject, ?AddressExtensionInterface $result = null): AddressExtensionInterface
    {
        if (!$result instanceof AddressExtensionInterface) {
            /** @var AddressExtensionInterface $result */
            $result = $this->extensionAttributesFactory->create($subject::class);
            $subject->setExtensionAttributes($result);
        }

        if (empty($subject->getId())) {
            return $result;
        }

        if ($result->getTiltaBuyer() === null) {
            try {
                $buyerData = $this->repository->getByCustomerAddressId((int) $subject->getId());
                $result->setTiltaBuyer($buyerData);
            } catch (NoSuchEntityException) {
            }
        }

        return $result;
    }
}
