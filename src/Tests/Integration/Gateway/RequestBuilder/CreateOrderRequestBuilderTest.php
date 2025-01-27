<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Integration\Gateway\RequestBuilder;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\Order\OrderAdapter;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tilta\Payment\Api\CustomerAddressBuyerRepositoryInterface;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;
use Tilta\Payment\Gateway\RequestBuilder\Common\AddressBuilder;
use Tilta\Payment\Gateway\RequestBuilder\Common\AmountBuilder;
use Tilta\Payment\Gateway\RequestBuilder\Common\LineItemsBuilder;
use Tilta\Payment\Gateway\RequestBuilder\CreateOrderRequestBuilder;
use Tilta\Payment\Observer\TiltaPaymentDataAssignAdditionalData;
use Tilta\Payment\Service\ConfigService;
use Tilta\Sdk\Model\Address;
use Tilta\Sdk\Model\Amount;
use Tilta\Sdk\Model\Order\LineItem;
use Tilta\Sdk\Model\Request\Order\CreateOrderRequestModel;

#[
    DataFixture(ProductFixture::class, as: 'product'),
    DataFixture(GuestCart::class, as: 'cart'),
    DataFixture(SetGuestEmail::class, ['cart_id' => '$cart.id$']),
    DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
    DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$', 'address' => ['company' => 'Test GmbH']]),
    DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
    DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
    DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
    DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order')
]
class CreateOrderRequestBuilderTest extends TestCase
{
    private const TEST_MERCHANT_ID = 'test-merchant-id';

    private const TEST_BUYER_EXTERNAL_ID = 'test-buyer-external-id';

    public function testBuild(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');
        self::assertNotNull($order);


        $orderAdapter = $objectManager->create(OrderAdapter::class, ['order' => $order]);
        $paymentDO = $objectManager->create(Payment::class, [
            'data' => [
                OrderPaymentInterface::ADDITIONAL_INFORMATION => [
                    TiltaPaymentDataAssignAdditionalData::PAYMENT_METHOD => 'payment-method',
                    TiltaPaymentDataAssignAdditionalData::PAYMENT_TERM => 'payment-term',
                ],
            ],
        ]);
        $buildSubject = ['payment' => new PaymentDataObject($orderAdapter, $paymentDO)];
        $result = $this->getBuilder()->build($buildSubject);
        self::assertIsArray($result);
        self::assertArrayHasKey('request_model', $result);
        self::assertInstanceOf(CreateOrderRequestModel::class, $result['request_model']);
        /** @var CreateOrderRequestModel $model */
        $model = $result['request_model'];

        self::assertEquals(self::TEST_MERCHANT_ID, $model->getMerchantExternalId());
        self::assertEquals(self::TEST_BUYER_EXTERNAL_ID, $model->getBuyerExternalId());
        self::assertEquals('payment-method', $model->getPaymentMethod());
        self::assertEquals('payment-term', $model->getPaymentTerm());
        self::assertEquals($order->getIncrementId(), $model->getOrderExternalId());
        self::assertNotEmpty($model->getAmount());
        self::assertNotEmpty($model->getDeliveryAddress());
        self::assertCount(1, $model->getLineItems());
    }

    public function testIfExceptionIsThrownOnMissingPaymentData(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');
        self::assertNotNull($order);


        $orderAdapter = $objectManager->create(OrderAdapter::class, ['order' => $order]);
        $paymentDO = $objectManager->create(Payment::class);
        $buildSubject = ['payment' => new PaymentDataObject($orderAdapter, $paymentDO)];

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Missing payment information.');
        $this->getBuilderNoExpects()->build($buildSubject);
    }

    public function testIfExceptionIsThrownOnMissingBuyer(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');
        self::assertNotNull($order);


        $orderAdapter = $objectManager->create(OrderAdapter::class, ['order' => $order]);
        $paymentDO = $objectManager->create(Payment::class);
        $buildSubject = ['payment' => new PaymentDataObject($orderAdapter, $paymentDO)];

        $buyerRepo = $this->createMock(CustomerAddressBuyerRepositoryInterface::class);
        $buyerRepo->method('getByCustomerAddressId')->willThrowException($this->createMock(NoSuchEntityException::class));

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Buyer does not have valid facility.');

        $this->getBuilderNoExpects($buyerRepo)->build($buildSubject);
    }

    public function testIfExceptionIsThrownOnMissingBuyerExternalId(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');
        self::assertNotNull($order);


        $orderAdapter = $objectManager->create(OrderAdapter::class, ['order' => $order]);
        $paymentDO = $objectManager->create(Payment::class);
        $buildSubject = ['payment' => new PaymentDataObject($orderAdapter, $paymentDO)];

        $buyer = ObjectManager::getInstance()->create(CustomerAddressBuyerInterface::class)
            ->setBuyerExternalId('')
            ->setFacilityTotalAmount(100);
        $buyerRepo = $this->createMock(CustomerAddressBuyerRepositoryInterface::class);
        $buyerRepo->method('getByCustomerAddressId')->willReturn($buyer);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Buyer does not have valid facility.');

        $this->getBuilderNoExpects($buyerRepo)->build($buildSubject);
    }

    public function testIfExceptionIsThrownOnMissingBuyerFacility(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');
        self::assertNotNull($order);


        $orderAdapter = $objectManager->create(OrderAdapter::class, ['order' => $order]);
        $paymentDO = $objectManager->create(Payment::class);
        $buildSubject = ['payment' => new PaymentDataObject($orderAdapter, $paymentDO)];

        $buyer = ObjectManager::getInstance()->create(CustomerAddressBuyerInterface::class)
            ->setBuyerExternalId(self::TEST_BUYER_EXTERNAL_ID)
            ->setFacilityTotalAmount(null);
        $buyerRepo = $this->createMock(CustomerAddressBuyerRepositoryInterface::class);
        $buyerRepo->method('getByCustomerAddressId')->willReturn($buyer);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Buyer does not have valid facility.');

        $this->getBuilderNoExpects($buyerRepo)->build($buildSubject);
    }

    private function getBuilder(CustomerAddressBuyerRepositoryInterface $buyerRepo = null): CreateOrderRequestBuilder
    {
        $amountBuilder = $this->createMock(AmountBuilder::class);
        $amountBuilder->expects($this->once())->method('createForOrder')->willReturn($this->createMock(Amount::class));
        $lineItemsBuilder = $this->createMock(LineItemsBuilder::class);
        $lineItemsBuilder->expects($this->atLeastOnce())->method('buildForOrder')->willReturn([$this->createMock(LineItem::class)]);
        $addressBuilder = $this->createMock(AddressBuilder::class);
        $addressBuilder->expects($this->atLeastOnce())->method('buildForOrderAddress')->willReturn($this->createMock(Address::class));
        $configService = $this->createMock(ConfigService::class);
        $configService->method('getMerchantExternalId')->willReturn(self::TEST_MERCHANT_ID);
        $buyerRepo = $buyerRepo ?: $this->createDefaultBuyerRepoMock();

        return new CreateOrderRequestBuilder($lineItemsBuilder, $addressBuilder, $amountBuilder, $configService, $buyerRepo);
    }

    private function getBuilderNoExpects(CustomerAddressBuyerRepositoryInterface $buyerRepo = null): CreateOrderRequestBuilder
    {
        $amountBuilder = $this->createMock(AmountBuilder::class);
        $amountBuilder->method('createForOrder')->willReturn($this->createMock(Amount::class));
        $lineItemsBuilder = $this->createMock(LineItemsBuilder::class);
        $lineItemsBuilder->method('buildForOrder')->willReturn([$this->createMock(LineItem::class)]);
        $addressBuilder = $this->createMock(AddressBuilder::class);
        $addressBuilder->method('buildForOrderAddress')->willReturn($this->createMock(Address::class));
        $configService = $this->createMock(ConfigService::class);
        $configService->method('getMerchantExternalId')->willReturn(self::TEST_MERCHANT_ID);
        $buyerRepo = $buyerRepo ?: $this->createDefaultBuyerRepoMock();

        return new CreateOrderRequestBuilder($lineItemsBuilder, $addressBuilder, $amountBuilder, $configService, $buyerRepo);
    }

    private function createDefaultBuyerRepoMock(): CustomerAddressBuyerRepositoryInterface
    {
        $buyer = ObjectManager::getInstance()->create(CustomerAddressBuyerInterface::class)
            ->setBuyerExternalId(self::TEST_BUYER_EXTERNAL_ID)
            ->setFacilityTotalAmount(100);
        $buyerRepo = $this->createMock(CustomerAddressBuyerRepositoryInterface::class);
        $buyerRepo->method('getByCustomerAddressId')->willReturn($buyer);

        return $buyerRepo;
    }
}
