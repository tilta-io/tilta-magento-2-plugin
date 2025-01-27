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
use Magento\Customer\Test\Fixture\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\Order\OrderAdapter;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;
use Magento\SalesSequence\Model\Manager;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tilta\Payment\Gateway\RequestBuilder\Common\AddressBuilder;
use Tilta\Payment\Gateway\RequestBuilder\Common\AmountBuilder;
use Tilta\Payment\Gateway\RequestBuilder\Common\LineItemsBuilder;
use Tilta\Payment\Gateway\RequestBuilder\CreateInvoiceRequestBuilder;
use Tilta\Sdk\Model\Address;
use Tilta\Sdk\Model\Amount;
use Tilta\Sdk\Model\Order\LineItem;
use Tilta\Sdk\Model\Request\Invoice\CreateInvoiceRequestModel;

#[
    DataFixture(ProductFixture::class, as: 'product'),
    DataFixture(Customer::class, as: 'place-order'),
    DataFixture(GuestCart::class, as: 'cart'),
    DataFixture(SetGuestEmail::class, ['cart_id' => '$cart.id$']),
    DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
    DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$', 'address' => ['company' => 'Test GmbH']]),
    DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
    DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
    DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
    DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
    DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
]
class CreateInvoiceRequestBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');
        /** @var InvoiceInterface $invoice */
        $invoice = DataFixtureStorageManager::getStorage()->get('invoice');
        self::assertNotNull($order);
        self::assertNotNull($invoice);

        $orderAdapter = $objectManager->create(OrderAdapter::class, ['order' => $order]);
        $payment = $objectManager->create(Payment::class, [
            'data' => [
                '_tilta_invoice_to_process' => $invoice,
            ],
        ]);

        $buildSubject = ['payment' => new PaymentDataObject($orderAdapter, $payment)];
        $result = $this->getBuilder()->build($buildSubject);
        self::assertIsArray($result);
        self::assertIsArray($result);
        self::assertArrayHasKey('request_model', $result);
        self::assertInstanceOf(CreateInvoiceRequestModel::class, $result['request_model']);
        /** @var CreateInvoiceRequestModel $model */
        $model = $result['request_model'];

        self::assertEquals($invoice->getIncrementId(), $model->getInvoiceNumber());
        self::assertEquals($invoice->getIncrementId(), $model->getInvoiceExternalId());
        self::assertEquals([$order->getIncrementId()], $model->getOrderExternalIds());
        self::assertNotEmpty($model->getAmount());
        self::assertNotEmpty($model->getBillingAddress());
        self::assertCount(1, $model->getLineItems());
    }

    public function testIfExceptionIsThrownOnMissingInvoice(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');
        /** @var InvoiceInterface $invoice */
        $invoice = DataFixtureStorageManager::getStorage()->get('invoice');
        self::assertNotNull($order);
        self::assertNotNull($invoice);

        $orderAdapter = $objectManager->create(OrderAdapter::class, ['order' => $order]);
        $payment = $objectManager->create(Payment::class);

        $buildSubject = ['payment' => new PaymentDataObject($orderAdapter, $payment)];
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invoice was not found.');
        $this->getBuilderWithoutExpect()->build($buildSubject);
    }

    private function getBuilder(): CreateInvoiceRequestBuilder
    {
        $amountBuilder = $this->createMock(AmountBuilder::class);
        $amountBuilder->expects($this->atLeastOnce())->method('createForInvoice')->willReturn($this->createMock(Amount::class));
        $lineItemsBuilder = $this->createMock(LineItemsBuilder::class);
        $lineItemsBuilder->expects($this->atLeastOnce())->method('buildForInvoice')->willReturn([$this->createMock(LineItem::class)]);
        $addressBuilder = $this->createMock(AddressBuilder::class);
        $addressBuilder->expects($this->once())->method('buildForOrderAddress')->willReturn($this->createMock(Address::class));

        return new CreateInvoiceRequestBuilder(
            $this->createMock(Manager::class),
            $amountBuilder,
            $addressBuilder,
            $lineItemsBuilder,
        );
    }

    private function getBuilderWithoutExpect(): CreateInvoiceRequestBuilder
    {
        $amountBuilder = $this->createMock(AmountBuilder::class);
        $amountBuilder->method('createForInvoice')->willReturn($this->createMock(Amount::class));
        $lineItemsBuilder = $this->createMock(LineItemsBuilder::class);
        $lineItemsBuilder->method('buildForInvoice')->willReturn([$this->createMock(LineItem::class)]);
        $addressBuilder = $this->createMock(AddressBuilder::class);
        $addressBuilder->method('buildForOrderAddress')->willReturn($this->createMock(Address::class));

        return new CreateInvoiceRequestBuilder(
            $this->createMock(Manager::class),
            $amountBuilder,
            $addressBuilder,
            $lineItemsBuilder,
        );
    }
}
