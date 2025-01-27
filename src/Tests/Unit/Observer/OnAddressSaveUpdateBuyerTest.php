<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Unit\Observer;

use Exception;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Address;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tilta\Payment\Api\CustomerAddressBuyerRepositoryInterface;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;
use Tilta\Payment\Observer\OnAddressSaveUpdateBuyer;
use Tilta\Payment\Service\BuyerService;

class OnAddressSaveUpdateBuyerTest extends TestCase
{
    public function testBuyerDataGotAutomaticallyUpdated(): void
    {
        $buyerService = $this->createMock(BuyerService::class);
        $addressRepository = $this->createMock(AddressRepositoryInterface::class);
        $buyerRepository = $this->createMock(CustomerAddressBuyerRepositoryInterface::class);
        $observer = new OnAddressSaveUpdateBuyer($buyerService, $addressRepository, $buyerRepository, $this->createMock(LoggerInterface::class));

        $addressEntity = $this->createMock(AddressInterface::class);
        $addressRepository->expects($this->once())->method('getById')->with(12345)->willReturn($addressEntity);
        $buyerRepository->expects($this->once())->method('getByCustomerAddressId')->with(12345)->willReturn($this->createMock(CustomerAddressBuyerInterface::class));
        $buyerService->expects($this->once())->method('upsertBuyer')->with($addressEntity);

        $observer->execute(new Observer([
            'data_object' => $this->getMockedAddress(),
        ]));
    }

    public function testNoFatalErrorOccursIfNoBuyerExist(): void
    {
        $buyerService = $this->createMock(BuyerService::class);
        $addressRepository = $this->createMock(AddressRepositoryInterface::class);
        $buyerRepository = $this->createMock(CustomerAddressBuyerRepositoryInterface::class);
        $observer = new OnAddressSaveUpdateBuyer($buyerService, $addressRepository, $buyerRepository, $this->createMock(LoggerInterface::class));

        $buyerRepository->expects($this->once())->method('getByCustomerAddressId')->with(12345)->willThrowException(new NoSuchEntityException());
        $buyerService->expects($this->never())->method('upsertBuyer');

        $observer->execute(new Observer([
            'data_object' => $this->getMockedAddress(),
        ]));
    }

    public function testNoFatalErrorOccursOnErrorOnUpsert(): void
    {
        $buyerService = $this->createMock(BuyerService::class);
        $addressRepository = $this->createMock(AddressRepositoryInterface::class);
        $buyerRepository = $this->createMock(CustomerAddressBuyerRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $observer = new OnAddressSaveUpdateBuyer($buyerService, $addressRepository, $buyerRepository, $logger);

        $addressEntity = $this->createMock(AddressInterface::class);
        $addressRepository->expects($this->once())->method('getById')->with(12345)->willReturn($addressEntity);
        $buyerRepository->expects($this->once())->method('getByCustomerAddressId')->with(12345)->willReturn($this->createMock(CustomerAddressBuyerInterface::class));
        $buyerService->expects($this->once())->method('upsertBuyer')->willThrowException(new Exception('exception should be only logged'));
        $logger->expects($this->once())->method('error');

        $observer->execute(new Observer([
            'data_object' => $this->getMockedAddress(),
        ]));
    }

    private function getMockedAddress(): Address
    {
        /** @var Address $address */
        $address = (new ObjectManager($this))->getObject(Address::class, [
            'data' => [
                'id' => '12345',
            ],
        ]);

        return $address;
    }
}
