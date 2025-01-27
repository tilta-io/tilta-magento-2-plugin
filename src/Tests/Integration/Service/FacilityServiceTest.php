<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Integration\Service;

use DateTime;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Test\Fixture\Customer;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tilta\Payment\Api\CustomerAddressBuyerRepositoryInterface;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;
use Tilta\Payment\Service\BuyerService;
use Tilta\Payment\Service\FacilityService;
use Tilta\Payment\Service\RequestServiceFactory;
use Tilta\Payment\Tests\Fixture\BuyerFixture;
use Tilta\Sdk\Exception\GatewayException\Facility\DuplicateFacilityException;
use Tilta\Sdk\Model\Response\Facility\GetFacilityResponseModel;
use Tilta\Sdk\Service\Request\Facility\CreateFacilityRequest;
use Tilta\Sdk\Service\Request\Facility\GetFacilityRequest;

class FacilityServiceTest extends TestCase
{
    #[
        DataFixture(Customer::class, [
            CustomerInterface::KEY_ADDRESSES => [[AddressInterface::COMPANY => 'Test GmbH', AddressInterface::DEFAULT_BILLING => true]],
        ], as: 'customer'),
        DataFixture(BuyerFixture::class, [CustomerAddressBuyerInterface::CUSTOMER_ADDRESS_ID => '$customer.default_billing$'], as: 'buyer'),
    ]
    public function testCreateFacility(): void
    {
        /** @var CustomerInterface $customer */
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        /** @var CustomerAddressBuyerInterface $buyer */
        $buyer = DataFixtureStorageManager::getStorage()->get('buyer');

        /** @var AddressInterface $address */
        self::assertInstanceOf(\Magento\Customer\Model\Customer::class, $customer);
        self::assertInstanceOf(CustomerAddressBuyerInterface::class, $buyer);
        self::assertIsNumeric($customer->getDefaultBilling());

        $objectManager = ObjectManager::getInstance();
        $address = $objectManager->get(AddressRepositoryInterface::class)->getById((int) $customer->getDefaultBilling());

        $buyerService = $this->createMock(BuyerService::class);
        $buyerService->expects($this->once())->method('upsertBuyer')->with($address);

        $serviceFactory = $this->createMock(RequestServiceFactory::class);
        $serviceFactory->method('get')->willReturnMap([
            [CreateFacilityRequest::class, $createRequest = $this->createMock(CreateFacilityRequest::class)],
            [GetFacilityRequest::class, $getRequest = $this->createMock(GetFacilityRequest::class)],
        ]);

        $getRequest->method('execute')->willReturn((new GetFacilityResponseModel([
            'status' => 'OK',
            'expires_at' => DateTime::createFromFormat('Y-m-d H:i:s', '2050-05-26 04:00:00')->format('U'),
            'currency' => 'EUR',
            'total_amount' => 3000,
            'available_amount' => 50,
            'used_amount' => 0,
            'pending_orders_amount' => 0,
            'buyer_external_id' => 'test-buyer-id',
        ])));

        $buyerRepository = $objectManager->get(CustomerAddressBuyerRepositoryInterface::class);
        $service = new FacilityService($serviceFactory, $buyerRepository, $buyerService);

        $service->createFacilityForBuyerIfNotExist($address);

        $buyerData = $buyerRepository->getByCustomerAddressId((int) $address->getId());
        self::assertNotNull($buyerData);
        self::assertEquals(3000, $buyerData->getFacilityTotalAmount(), 'total amount should be updated from response');
        self::assertEquals('2050-05-26 04:00:00', $buyerData->getFacilityValidUntil(), 'valid-until should be updated from response');
    }

    #[
        DataFixture(Customer::class, [CustomerInterface::KEY_ADDRESSES => [[AddressInterface::COMPANY => 'Test GmbH', AddressInterface::DEFAULT_BILLING => true]]], as: 'customer'),
        DataFixture(BuyerFixture::class, [CustomerAddressBuyerInterface::CUSTOMER_ADDRESS_ID => '$customer.default_billing$'], as: 'buyer'),
    ]
    public function testFacilityGotUpdatedCorrectlyIfAlreadyExist(): void
    {
        /** @var CustomerInterface $customer */
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        /** @var CustomerAddressBuyerInterface $buyer */
        $buyer = DataFixtureStorageManager::getStorage()->get('buyer');

        /** @var AddressInterface $address */
        self::assertInstanceOf(\Magento\Customer\Model\Customer::class, $customer);
        self::assertInstanceOf(CustomerAddressBuyerInterface::class, $buyer);
        self::assertIsNumeric($customer->getDefaultBilling());

        $objectManager = ObjectManager::getInstance();
        $address = $objectManager->get(AddressRepositoryInterface::class)->getById((int) $customer->getDefaultBilling());

        $buyerService = $this->createMock(BuyerService::class);
        $buyerService->expects($this->once())->method('upsertBuyer')->with($address);

        $serviceFactory = $this->createMock(RequestServiceFactory::class);
        $serviceFactory->method('get')->willReturnMap([
            [CreateFacilityRequest::class, $createRequest = $this->createMock(CreateFacilityRequest::class)],
            [GetFacilityRequest::class, $getRequest = $this->createMock(GetFacilityRequest::class)],
        ]);

        $createRequest->method('execute')->willThrowException(new DuplicateFacilityException(500, [], []));
        $getRequest->method('execute')->willReturn((new GetFacilityResponseModel([
            'status' => 'OK',
            'expires_at' => DateTime::createFromFormat('Y-m-d H:i:s', '2050-05-26 04:00:00')->format('U'),
            'currency' => 'EUR',
            'total_amount' => 3000,
            'available_amount' => 50,
            'used_amount' => 0,
            'pending_orders_amount' => 0,
            'buyer_external_id' => 'test-buyer-id',
        ])));

        $buyerRepository = $objectManager->get(CustomerAddressBuyerRepositoryInterface::class);
        $service = new FacilityService($serviceFactory, $buyerRepository, $buyerService);

        $service->createFacilityForBuyerIfNotExist($address);

        $buyerData = $buyerRepository->getByCustomerAddressId((int) $address->getId());
        self::assertNotNull($buyerData);
        self::assertEquals(3000, $buyerData->getFacilityTotalAmount(), 'total amount should be updated from response');
        self::assertEquals('2050-05-26 04:00:00', $buyerData->getFacilityValidUntil(), 'valid-until should be updated from response');
    }
}
