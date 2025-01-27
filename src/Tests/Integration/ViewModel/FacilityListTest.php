<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Integration\ViewModel;

use DateTime;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use Monolog\Test\TestCase;
use Tilta\Payment\Service\FacilityService;
use Tilta\Payment\ViewModel\CustomerAccount\FacilityList;
use Tilta\Sdk\Exception\GatewayException\UnexpectedServerResponse;
use Tilta\Sdk\Model\Response\Facility\GetFacilityResponseModel;

class FacilityListTest extends TestCase
{
    #[
        DataFixture(Customer::class, [
            CustomerInterface::KEY_ADDRESSES => [
                [AddressInterface::COMPANY => null, AddressInterface::DEFAULT_BILLING => true],
                [AddressInterface::COMPANY => null, AddressInterface::DEFAULT_BILLING => true],
                [AddressInterface::COMPANY => null, AddressInterface::DEFAULT_BILLING => true],
                [AddressInterface::COMPANY => 'Test GmbH', AddressInterface::DEFAULT_BILLING => true],
                [AddressInterface::COMPANY => 'Test 2 GmbH', AddressInterface::DEFAULT_BILLING => true],
            ],
        ], as: 'customer'),
    ]
    public function testGetListOnlyReturnsBusinessAddresses(): void
    {
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        self::assertNotNull($customer);

        $customerSession = $this->createMock(Session::class);
        $customerSession->method('getCustomer')->willReturn($customer);
        $customerSession->method('getCustomerId')->willReturn($customer->getId());
        $service = ObjectManager::getInstance()->create(FacilityList::class, [
            'customerSession' => $customerSession,
        ]);

        $list = $service->getList();
        self::assertCount(2, $list);
    }

    #[
        DataFixture(Customer::class, [
            CustomerInterface::KEY_ADDRESSES => [
                [AddressInterface::COMPANY => null, AddressInterface::DEFAULT_BILLING => true],
                [AddressInterface::COMPANY => null, AddressInterface::DEFAULT_BILLING => true],
                [AddressInterface::COMPANY => null, AddressInterface::DEFAULT_BILLING => true],
            ],
        ], as: 'customer'),
    ]
    public function testGetListReturnsNothingIfNoBusinessAddressExist(): void
    {
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        self::assertNotNull($customer);

        $customerSession = $this->createMock(Session::class);
        $customerSession->method('getCustomer')->willReturn($customer);
        $customerSession->method('getCustomerId')->willReturn($customer->getId());
        $service = ObjectManager::getInstance()->create(FacilityList::class, [
            'customerSession' => $customerSession,
        ]);

        $list = $service->getList();
        self::assertCount(0, $list);
    }

    #[
        DataFixture(Customer::class, [CustomerInterface::KEY_ADDRESSES => [[AddressInterface::COMPANY => 'Test GmbH', AddressInterface::DEFAULT_BILLING => true]]], as: 'customer'),
    ]
    public function testGetFacilityUsage(): void
    {
        $objectManager = ObjectManager::getInstance();
        $addressRepository = $objectManager->get(AddressRepositoryInterface::class);
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        self::assertNotNull($customer);
        $address = $addressRepository->getById($customer->getDefaultBillingAddress()->getId());

        $customerSession = $this->createMock(Session::class);
        $facilityService = $this->createMock(FacilityService::class);
        $priceCurrency = $this->createMock(PriceCurrencyInterface::class);
        $service = $objectManager->create(FacilityList::class, [
            'customerSession' => $customerSession,
            'facilityService' => $facilityService,
            'priceCurrency' => $priceCurrency,
        ]);

        $priceCurrency->method('convertAndFormat')->willReturnCallback(static fn (...$args) => $args[0]);

        $facilityService->method('getFacility')->with($address)->willReturn((new GetFacilityResponseModel([
            'status' => 'OK',
            'expires_at' => DateTime::createFromFormat('Y-m-d H:i:s', '2050-05-26 04:00:00')->format('U'),
            'currency' => 'EUR',
            'total_amount' => 10000,
            'available_amount' => 2500,
            'used_amount' => 500,
            'pending_orders_amount' => 0,
            'buyer_external_id' => 'test-buyer-id',
        ])));

        $result = $service->getFacilityUsage($address);
        self::assertInstanceOf(DataObject::class, $result);
        self::assertEquals(100, $result->getData('total_amount'));
        self::assertEquals(25, $result->getData('available_amount'));
        self::assertEquals(5, $result->getData('used_amount'));
        self::assertEquals(25.0, $result->getData('usage_percentage'));
    }

    #[
        DataFixture(Customer::class, [CustomerInterface::KEY_ADDRESSES => [[AddressInterface::COMPANY => 'Test GmbH', AddressInterface::DEFAULT_BILLING => true]]], as: 'customer'),
    ]
    public function testGetFacilityUsageNotFailsOnNoFacility(): void
    {
        $objectManager = ObjectManager::getInstance();
        $addressRepository = $objectManager->get(AddressRepositoryInterface::class);
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        self::assertNotNull($customer);
        $address = $addressRepository->getById($customer->getDefaultBillingAddress()->getId());

        $facilityService = $this->createMock(FacilityService::class);
        $service = $objectManager->create(FacilityList::class);

        $facilityService->method('getFacility')->with($address)->willReturn(null);

        $result = $service->getFacilityUsage($address);
        self::assertNull($result);
    }

    #[
        DataFixture(Customer::class, [CustomerInterface::KEY_ADDRESSES => [[AddressInterface::COMPANY => 'Test GmbH', AddressInterface::DEFAULT_BILLING => true]]], as: 'customer'),
    ]
    public function testGetFacilityUsageDoesNotFailOnApiRequestFail(): void
    {
        $objectManager = ObjectManager::getInstance();
        $addressRepository = $objectManager->get(AddressRepositoryInterface::class);
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        self::assertNotNull($customer);
        $address = $addressRepository->getById($customer->getDefaultBillingAddress()->getId());

        $customerSession = $this->createMock(Session::class);
        $facilityService = $this->createMock(FacilityService::class);
        $priceCurrency = $this->createMock(PriceCurrencyInterface::class);
        $service = $objectManager->create(FacilityList::class, [
            'customerSession' => $customerSession,
            'facilityService' => $facilityService,
            'priceCurrency' => $priceCurrency,
        ]);

        $priceCurrency->method('convertAndFormat')->willReturnCallback(static fn (...$args) => $args[0]);

        $facilityService->method('getFacility')->with($address)->willThrowException(new UnexpectedServerResponse(123));

        $result = $service->getFacilityUsage($address);
        self::assertNull($result);
    }
}
