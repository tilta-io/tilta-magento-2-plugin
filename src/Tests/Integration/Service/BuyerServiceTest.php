<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Integration\Service;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Block\Widget\Telephone;
use Magento\Customer\Test\Fixture\Customer;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tilta\Payment\Api\CustomerAddressBuyerRepositoryInterface;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;
use Tilta\Payment\Exception\MissingBuyerInformationException;
use Tilta\Payment\Model\CustomerAddressBuyer;
use Tilta\Payment\Service\BuyerService;
use Tilta\Payment\Service\ConfigService;
use Tilta\Payment\Service\RequestServiceFactory;
use Tilta\Payment\Tests\Fixture\BuyerFixture;
use Tilta\Sdk\Exception\GatewayException\NotFoundException\BuyerNotFoundException;
use Tilta\Sdk\Model\Buyer;
use Tilta\Sdk\Model\ContactPerson;
use Tilta\Sdk\Model\Request\Buyer\CreateBuyerRequestModel;
use Tilta\Sdk\Model\Request\Buyer\UpdateBuyerRequestModel;
use Tilta\Sdk\Service\Request\Buyer\CreateBuyerRequest;
use Tilta\Sdk\Service\Request\Buyer\GetBuyerDetailsRequest;
use Tilta\Sdk\Service\Request\Buyer\UpdateBuyerRequest;

class BuyerServiceTest extends TestCase
{
    public function testGetBuyerExternalId(): void
    {
        $objectManager = ObjectManager::getInstance();
        $address = $objectManager->create(AddressInterface::class);

        $buyerService = $objectManager->create(BuyerService::class);
        self::assertNull($buyerService->getBuyerExternalId($address));

        $buyer = $objectManager->create(CustomerAddressBuyer::class);
        $buyer->setBuyerExternalId('test-123');
        $address->getExtensionAttributes()->setTiltaBuyer($buyer);
        self::assertEquals('test-123', $buyerService->getBuyerExternalId($address));
    }

    public function testGenerateBuyerExternalIdWithExistingId(): void
    {
        $objectManager = ObjectManager::getInstance();
        $address = $objectManager->create(AddressInterface::class);

        $configService = $this->createMock(ConfigService::class);
        $buyerService = $objectManager->create(BuyerService::class, [
            'configService' => $configService,
        ]);

        $buyer = $objectManager->create(CustomerAddressBuyer::class);
        $buyer->setBuyerExternalId('test-123');
        $address->getExtensionAttributes()->setTiltaBuyer($buyer);
        self::assertEquals('test-123', $buyerService->generateBuyerExternalId($address));
        self::assertEquals($buyerService->generateBuyerExternalId($address), $buyerService->generateBuyerExternalId($address), 'buyer-id should never be generated newly');
    }

    public function testGenerateBuyerExternalIdWithNotExistingId(): void
    {
        $objectManager = ObjectManager::getInstance();
        $address = $objectManager->create(AddressInterface::class);

        $configService = $this->createMock(ConfigService::class);
        $buyerService = $objectManager->create(BuyerService::class, [
            'config' => $configService,
        ]);

        self::assertNotEmpty($buyerService->generateBuyerExternalId($address));
        self::assertNotEquals($buyerService->generateBuyerExternalId($address), $buyerService->generateBuyerExternalId($address), 'buyer-id should be never equal');

        $configService->method('getBuyerExternalIdPrefix')->willReturn('prefix-');
        self::assertNotEmpty($buyerService->generateBuyerExternalId($address));
        self::assertStringStartsWith('prefix-', $buyerService->generateBuyerExternalId($address));
        self::assertNotEquals($buyerService->generateBuyerExternalId($address), $buyerService->generateBuyerExternalId($address), 'buyer-id should be never equal');
    }

    #[
        DataFixture(Customer::class, [CustomerInterface::KEY_ADDRESSES => [[AddressInterface::COMPANY => 'Test GmbH', AddressInterface::DEFAULT_BILLING => true]]], as: 'customer'),
    ]
    public function testUpdateCustomerAddressDataWithNoExistingBuyer(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $addressRepo = $objectManager->get(AddressRepositoryInterface::class);
        $address = $addressRepo->getById((int) $customer->getDefaultBillingAddress()->getId());

        $customerAddressRepo = $this->createMock(AddressRepositoryInterface::class);
        $service = $objectManager->create(BuyerService::class, [
            'customerAddressRepository' => $customerAddressRepo,
        ]);

        $customerAddressRepo->expects($this->once())->method('save')->willReturnCallback(static function (AddressInterface $address) use ($addressRepo): void {
            self::assertEquals('+49123456789', $address->getTelephone());
            $addressRepo->save($address);
        });

        $service->updateCustomerAddressData($address, [
            Telephone::ATTRIBUTE_CODE => '+49123456789',
            CustomerAddressBuyerInterface::INCORPORATED_AT => '2024-09-30',
            CustomerAddressBuyerInterface::LEGAL_FORM => 'GmbH',
        ]);

        $addressBuyerRepo = $objectManager->get(CustomerAddressBuyerRepositoryInterface::class);
        $buyer = $addressBuyerRepo->getByCustomerAddressId((int) $address->getId());
        self::assertEquals((int) $address->getId(), $buyer->getCustomerAddressId());
        self::assertNotNull($buyer->getBuyerExternalId());
        self::assertEquals('GmbH', $buyer->getLegalForm());
        self::assertEquals('2024-09-30', $buyer->getIncorporatedAt());
        self::assertNull($buyer->getFacilityTotalAmount(), 'facility data should be null');
        self::assertNull($buyer->getFacilityValidUntil(), 'facility data should be null');
    }

    #[
        DataFixture(Customer::class, [CustomerInterface::KEY_ADDRESSES => [[AddressInterface::COMPANY => 'Test GmbH', AddressInterface::DEFAULT_BILLING => true]]], as: 'customer'),
        DataFixture(BuyerFixture::class, [CustomerAddressBuyerInterface::CUSTOMER_ADDRESS_ID => '$customer.default_billing$'], as: 'buyer'),
    ]
    public function testUpdateCustomerAddressDataWithExistingBuyer(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $addressRepo = $objectManager->get(AddressRepositoryInterface::class);
        $address = $addressRepo->getById((int) $customer->getDefaultBillingAddress()->getId());

        $customerAddressRepo = $this->createMock(AddressRepositoryInterface::class);
        $service = $objectManager->create(BuyerService::class, [
            'customerAddressRepository' => $customerAddressRepo,
        ]);

        $customerAddressRepo->expects($this->once())->method('save')->willReturnCallback(static function (AddressInterface $address) use ($addressRepo): void {
            self::assertEquals('+49123456789', $address->getTelephone());
            $addressRepo->save($address);
        });

        $service->updateCustomerAddressData($address, [
            Telephone::ATTRIBUTE_CODE => '+49123456789',
            CustomerAddressBuyerInterface::INCORPORATED_AT => '2024-09-30',
            CustomerAddressBuyerInterface::LEGAL_FORM => 'test-legal-form',
        ]);

        $address = $addressRepo->getById((int) $customer->getDefaultBillingAddress()->getId());
        $service->updateCustomerAddressData($address, [
            Telephone::ATTRIBUTE_CODE => '+49123456789',
        ]); //update again, to test correct behaviour for same phone number (save on address-repository should not be executed again)

        $addressBuyerRepo = $objectManager->get(CustomerAddressBuyerRepositoryInterface::class);
        $buyer = $addressBuyerRepo->getByCustomerAddressId((int) $address->getId());
        self::assertEquals((int) $address->getId(), $buyer->getCustomerAddressId());
        self::assertStringStartsWith('buyer-external-id-', $buyer->getBuyerExternalId());
        self::assertEquals('test-legal-form', $buyer->getLegalForm());
        self::assertEquals('2024-09-30', $buyer->getIncorporatedAt());
        self::assertNotNull($buyer->getFacilityTotalAmount());
        self::assertNotNull($buyer->getFacilityValidUntil());
    }

    #[
        DataFixture(Customer::class, [CustomerInterface::KEY_ADDRESSES => [[
            AddressInterface::FIRSTNAME => 'test-firstname',
            AddressInterface::LASTNAME => 'test-lastname',
            AddressInterface::TELEPHONE => 'test-phone',
            AddressInterface::COMPANY => 'Test GmbH',
            AddressInterface::STREET => ['test-street 46'],
            AddressInterface::COUNTRY_ID => 'DE',
            AddressInterface::REGION_ID => null,
            AddressInterface::POSTCODE => '45678',
            AddressInterface::CITY => 'test-city',
            AddressInterface::DEFAULT_BILLING => true,
        ]]], as: 'customer'),
    ]
    public function testUpsertBuyerCreatesNewBuyer(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $addressRepo = $objectManager->get(AddressRepositoryInterface::class);
        $address = $addressRepo->getById((int) $customer->getDefaultBillingAddress()->getId());

        $customerAddressRepo = $this->createMock(AddressRepositoryInterface::class);
        $requestServiceFactory = $this->createMock(RequestServiceFactory::class);
        $requestServiceFactory->method('get')->willReturnMap([
            [GetBuyerDetailsRequest::class, $getRequest = $this->createMock(GetBuyerDetailsRequest::class)],
            [CreateBuyerRequest::class, $createRequest = $this->createMock(CreateBuyerRequest::class)],
            [UpdateBuyerRequest::class, $updateRequest = $this->createMock(UpdateBuyerRequest::class)],
        ]);

        $service = $objectManager->create(BuyerService::class, [
            'customerAddressRepository' => $customerAddressRepo,
            'requestServiceFactory' => $requestServiceFactory,
        ]);

        $service->updateCustomerAddressData($address, [
            Telephone::ATTRIBUTE_CODE => '+49123456789',
            CustomerAddressBuyerInterface::INCORPORATED_AT => '2024-09-30',
            CustomerAddressBuyerInterface::LEGAL_FORM => 'GmbH',
        ]);

        $getRequest->expects($this->once())->method('execute')->willThrowException(new BuyerNotFoundException('test-123'));
        $updateRequest->expects($this->never())->method('execute');
        $createRequest->expects($this->once())->method('execute')->willReturnCallback(static function (CreateBuyerRequestModel $model) use ($customer): void {
            self::assertEquals('GmbH', $model->getLegalForm());
            self::assertEquals('Test GmbH', $model->getLegalName());
            self::assertEquals('Test GmbH', $model->getTradingName());
            self::assertEquals('test-street', $model->getBusinessAddress()->getStreet());
            self::assertEquals('46', $model->getBusinessAddress()->getHouseNumber());
            self::assertEquals('DE', $model->getBusinessAddress()->getCountry());
            self::assertEquals('45678', $model->getBusinessAddress()->getPostcode());
            self::assertEquals('test-city', $model->getBusinessAddress()->getCity());
            self::assertIsArray($model->getContactPersons());
            self::assertCount(1, $model->getContactPersons());
            self::assertContainsOnlyInstancesOf(ContactPerson::class, $model->getContactPersons());
            $contactPerson = $model->getContactPersons()[0];
            self::assertEquals('test-firstname', $contactPerson->getFirstName());
            self::assertEquals('test-lastname', $contactPerson->getLastName());
            self::assertEquals($customer->getEmail(), $contactPerson->getEmail());
            self::assertEquals('+49123456789', $contactPerson->getPhone());
            self::assertEquals($model->getBusinessAddress(), $contactPerson->getAddress());
        });

        $service->upsertBuyer($address);
    }

    #[
        DataFixture(Customer::class, [CustomerInterface::KEY_ADDRESSES => [[
            AddressInterface::FIRSTNAME => 'test-firstname',
            AddressInterface::LASTNAME => 'test-lastname',
            AddressInterface::TELEPHONE => 'test-phone',
            AddressInterface::COMPANY => 'Test GmbH',
            AddressInterface::STREET => ['test-street 46'],
            AddressInterface::COUNTRY_ID => 'DE',
            AddressInterface::REGION_ID => null,
            AddressInterface::POSTCODE => '45678',
            AddressInterface::CITY => 'test-city',
            AddressInterface::DEFAULT_BILLING => true,
        ]]], as: 'customer'),
        DataFixture(BuyerFixture::class, [CustomerAddressBuyerInterface::CUSTOMER_ADDRESS_ID => '$customer.default_billing$'], as: 'buyer'),
    ]
    public function testUpsertBuyerCreatesUpdateBuyer(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        /** @var CustomerAddressBuyerInterface $buyer */
        $buyer = DataFixtureStorageManager::getStorage()->get('buyer');
        $addressRepo = $objectManager->get(AddressRepositoryInterface::class);
        $address = $addressRepo->getById((int) $customer->getDefaultBillingAddress()->getId());

        $customerAddressRepo = $this->createMock(AddressRepositoryInterface::class);
        $requestServiceFactory = $this->createMock(RequestServiceFactory::class);
        $requestServiceFactory->method('get')->willReturnMap([
            [GetBuyerDetailsRequest::class, $getRequest = $this->createMock(GetBuyerDetailsRequest::class)],
            [CreateBuyerRequest::class, $createRequest = $this->createMock(CreateBuyerRequest::class)],
            [UpdateBuyerRequest::class, $updateRequest = $this->createMock(UpdateBuyerRequest::class)],
        ]);

        $service = $objectManager->create(BuyerService::class, [
            'customerAddressRepository' => $customerAddressRepo,
            'requestServiceFactory' => $requestServiceFactory,
        ]);

        $service->updateCustomerAddressData($address, [
            Telephone::ATTRIBUTE_CODE => '+49123456789',
            CustomerAddressBuyerInterface::INCORPORATED_AT => '2024-09-30',
            CustomerAddressBuyerInterface::LEGAL_FORM => 'GmbH',
        ]);

        $getRequest->expects($this->once())->method('execute')->willReturn(new Buyer());
        $createRequest->expects($this->never())->method('execute');
        $updateRequest->expects($this->once())->method('execute')->willReturnCallback(static function (UpdateBuyerRequestModel $model) use ($customer, $buyer): void {
            self::assertEquals($buyer->getBuyerExternalId(), $model->getExternalId());
            self::assertEquals('GmbH', $model->getLegalForm());
            self::assertEquals('Test GmbH', $model->getLegalName());
            self::assertEquals('Test GmbH', $model->getTradingName());
            self::assertEquals('test-street', $model->getBusinessAddress()->getStreet());
            self::assertEquals('46', $model->getBusinessAddress()->getHouseNumber());
            self::assertEquals('DE', $model->getBusinessAddress()->getCountry());
            self::assertEquals('45678', $model->getBusinessAddress()->getPostcode());
            self::assertEquals('test-city', $model->getBusinessAddress()->getCity());
            self::assertIsArray($model->getContactPersons());
            self::assertCount(1, $model->getContactPersons());
            self::assertContainsOnlyInstancesOf(ContactPerson::class, $model->getContactPersons());
            $contactPerson = $model->getContactPersons()[0];
            self::assertEquals('test-firstname', $contactPerson->getFirstName());
            self::assertEquals('test-lastname', $contactPerson->getLastName());
            self::assertEquals($customer->getEmail(), $contactPerson->getEmail());
            self::assertEquals('+49123456789', $contactPerson->getPhone());
            self::assertEquals($model->getBusinessAddress(), $contactPerson->getAddress());
        });

        $service->upsertBuyer($address);
    }

    public function testUpsertBuyerValidation(): void
    {
        $objectManager = ObjectManager::getInstance();
        $address = $objectManager->create(AddressInterface::class);

        $service = $objectManager->get(BuyerService::class);
        $this->expectException(MissingBuyerInformationException::class);

        try {
            $service->upsertBuyer($address);
        } catch (MissingBuyerInformationException $missingBuyerInformationException) {
            self::assertArrayHasKey(AddressInterface::TELEPHONE, $missingBuyerInformationException->getErrorMessages());
            self::assertArrayHasKey(AddressInterface::COMPANY, $missingBuyerInformationException->getErrorMessages());
            self::assertArrayHasKey(CustomerAddressBuyerInterface::INCORPORATED_AT, $missingBuyerInformationException->getErrorMessages());
            self::assertArrayHasKey(CustomerAddressBuyerInterface::LEGAL_FORM, $missingBuyerInformationException->getErrorMessages());
            throw $missingBuyerInformationException;
        }
    }

    public function testCanChangeCountryDoesNotFailOnMissingEntity(): void
    {
        $objectManager = ObjectManager::getInstance();
        $result = $objectManager->get(BuyerService::class)->canChangeCountry(99999999);
        self::assertTrue($result);
    }
}
