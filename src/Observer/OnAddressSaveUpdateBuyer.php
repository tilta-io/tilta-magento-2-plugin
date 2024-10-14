<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Observer;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Tilta\Payment\Api\CustomerAddressBuyerRepositoryInterface;
use Tilta\Payment\Service\BuyerService;

class OnAddressSaveUpdateBuyer implements ObserverInterface
{
    public function __construct(
        private readonly BuyerService $buyerService,
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly CustomerAddressBuyerRepositoryInterface $repository
    ) {
    }

    public function execute(Observer $observer): void
    {
        $address = $observer->getData('data_object');
        if (!$address instanceof AbstractAddress || empty($address->getId())) {
            return;
        }

        try {
            $address = $this->addressRepository->getById((int) $address->getId());
            $buyerData = $this->repository->getByCustomerAddressId((int) $address->getId());
        } catch (NoSuchEntityException) {
            return;
        }

        $this->buyerService->upsertBuyer($address);
    }
}
