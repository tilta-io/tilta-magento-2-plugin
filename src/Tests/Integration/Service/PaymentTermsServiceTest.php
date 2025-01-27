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
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use PHPUnit\Framework\TestCase;
use Throwable;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;
use Tilta\Payment\Gateway\RequestBuilder\Common\AmountBuilder;
use Tilta\Payment\Service\ConfigService;
use Tilta\Payment\Service\FacilityService;
use Tilta\Payment\Service\PaymentTermsService;
use Tilta\Payment\Service\RequestServiceFactory;
use Tilta\Payment\Tests\Fixture\BuyerFixture;
use Tilta\Sdk\Exception\GatewayException\Facility\NoActiveFacilityFoundException;
use Tilta\Sdk\Exception\GatewayException\NotFoundException\BuyerNotFoundException;
use Tilta\Sdk\Model\Response\PaymentTerm\GetPaymentTermsResponseModel;
use Tilta\Sdk\Service\Request\PaymentTerm\GetPaymentTermsRequest;
use Tilta\Sdk\Util\ResponseHelper;

class PaymentTermsServiceTest extends TestCase
{
    #[
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Customer::class, [
            CustomerInterface::KEY_ADDRESSES => [
                [AddressInterface::COMPANY => 'Test GmbH', AddressInterface::DEFAULT_BILLING => true],
                [AddressInterface::COMPANY => 'Test 2 GmbH'],
            ],
        ], as: 'customer'),
        DataFixture(BuyerFixture::class, [CustomerAddressBuyerInterface::CUSTOMER_ADDRESS_ID => '$customer.default_billing$'], as: 'buyer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
    ]
    public function testGetPaymentTermsForQuote(): void
    {
        /** @var CartInterface $cart */
        $cart = DataFixtureStorageManager::getStorage()->get('cart');

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getMerchantExternalId')->willReturn('test-merchant-external-id');
        $facilityService = $this->createMock(FacilityService::class);
        $requestService = $this->createMock(GetPaymentTermsRequest::class);
        $amountBuilder = $this->createMock(AmountBuilder::class);
        $requestServiceFactory = $this->createMock(RequestServiceFactory::class);
        $requestServiceFactory->method('get')->with(GetPaymentTermsRequest::class)->willReturn($requestService);
        $service = ObjectManager::getInstance()->create(PaymentTermsService::class, [
            'configService' => $configService,
            'facilityService' => $facilityService,
            'requestServiceFactory' => $requestServiceFactory,
            'amountBuilder' => $amountBuilder,
        ]);

        $amountBuilder->expects($this->once())->method('createForCart');
        $requestService->expects($this->once())->method('execute')->willReturn($this->getValidResponse());

        // should be always called to make sure facility is up-to-date
        $facilityService->expects($this->once())->method('updateFacilityOnCustomerAddress');

        self::assertInstanceOf(GetPaymentTermsResponseModel::class, $service->getPaymentTermsForQuote($cart));
    }

    #[
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Customer::class, [CustomerInterface::KEY_ADDRESSES => [
            [AddressInterface::COMPANY => 'Test GmbH', AddressInterface::DEFAULT_BILLING => true],
            [AddressInterface::COMPANY => 'Test 2 GmbH'],
        ]], as: 'customer'),
        DataFixture(BuyerFixture::class, [CustomerAddressBuyerInterface::CUSTOMER_ADDRESS_ID => '$customer.default_billing$'], as: 'buyer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
    ]
    public function testGetPaymentTermsForQuoteIfNoBuyerExist(): void
    {
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        /** @var CartInterface $cart */
        $cart = DataFixtureStorageManager::getStorage()->get('cart');

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getMerchantExternalId')->willReturn('test-merchant-external-id');
        $facilityService = $this->createMock(FacilityService::class);
        $requestService = $this->createMock(GetPaymentTermsRequest::class);
        $amountBuilder = $this->createMock(AmountBuilder::class);
        $requestServiceFactory = $this->createMock(RequestServiceFactory::class);
        $requestServiceFactory->method('get')->with(GetPaymentTermsRequest::class)->willReturn($requestService);
        $service = ObjectManager::getInstance()->create(PaymentTermsService::class, [
            'configService' => $configService,
            'facilityService' => $facilityService,
            'requestServiceFactory' => $requestServiceFactory,
            'amountBuilder' => $amountBuilder,
        ]);

        $amountBuilder->expects($this->never())->method('createForCart');
        $requestService->expects($this->never())->method('execute')->willReturn($this->getValidResponse());
        $facilityService->expects($this->never())->method('updateFacilityOnCustomerAddress');

        /** @var Address[] $additionalAddress */
        $additionalAddress = $customer->getAdditionalAddresses();
        self::assertContainsOnlyInstancesOf(Address::class, $additionalAddress);
        self::assertCount(1, $additionalAddress);
        self::assertNull($service->getPaymentTermsForQuote($cart, (int) $additionalAddress[0]->getId()));
    }

    /**
     * @dataProvider getPaymentTermsForQuoteHandlesExceptionsCorrectlyDataProvider
     */
    #[
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Customer::class, [CustomerInterface::KEY_ADDRESSES => [
            [AddressInterface::COMPANY => 'Test GmbH', AddressInterface::DEFAULT_BILLING => true],
            [AddressInterface::COMPANY => 'Test 2 GmbH'],
        ]], as: 'customer'),
        DataFixture(BuyerFixture::class, [CustomerAddressBuyerInterface::CUSTOMER_ADDRESS_ID => '$customer.default_billing$'], as: 'buyer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
    ]
    public function testGetPaymentTermsForQuoteHandlesExceptionsCorrectly(Throwable $exception): void
    {
        /** @var CartInterface $cart */
        $cart = DataFixtureStorageManager::getStorage()->get('cart');

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getMerchantExternalId')->willReturn('test-merchant-external-id');
        $facilityService = $this->createMock(FacilityService::class);
        $requestService = $this->createMock(GetPaymentTermsRequest::class);
        $amountBuilder = $this->createMock(AmountBuilder::class);
        $requestServiceFactory = $this->createMock(RequestServiceFactory::class);
        $requestServiceFactory->method('get')->with(GetPaymentTermsRequest::class)->willReturn($requestService);
        $service = ObjectManager::getInstance()->create(PaymentTermsService::class, [
            'configService' => $configService,
            'facilityService' => $facilityService,
            'requestServiceFactory' => $requestServiceFactory,
            'amountBuilder' => $amountBuilder,
        ]);

        $amountBuilder->expects($this->once())->method('createForCart');
        $facilityService->expects($this->never())->method('updateFacilityOnCustomerAddress');

        $requestService->expects($this->once())->method('execute')->willThrowException($exception);
        self::assertNull($service->getPaymentTermsForQuote($cart));
    }

    public static function getPaymentTermsForQuoteHandlesExceptionsCorrectlyDataProvider(): array
    {
        return [
            [new BuyerNotFoundException('test-123')],
            [new NoActiveFacilityFoundException('test-123')],
        ];
    }

    private function getValidResponse(): GetPaymentTermsResponseModel
    {
        return new GetPaymentTermsResponseModel([
            'facility' => [
                'status' => 'OK',
                'expires_at' => (new DateTime())->modify('+1 day')->format('U'),
                'currency' => 'EUR',
                'total_amount' => 9999,
                'available_amount' => 9999,
                'used_amount' => 0,
            ],
            'payment_terms' => [[
                'payment_method' => 'test-payment-method',
                'payment_term' => 'test-payment-term',
                'name' => 'test-name',
                'due_date' => DateTime::createFromFormat('Y-m-d', '2050-01-01')->format('U'),
                'amount' => ResponseHelper::PHPUNIT_OBJECT,
            ], [
                'payment_method' => 'test-payment-method2',
                'payment_term' => 'test-payment-term2',
                'name' => 'test-name2',
                'due_date' => DateTime::createFromFormat('Y-m-d', '2100-01-01')->format('U'),
                'amount' => ResponseHelper::PHPUNIT_OBJECT,
            ]],
        ]);
    }
}
