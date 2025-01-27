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
use Magento\Payment\Gateway\Data\Order\OrderAdapter;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Test\Fixture\Creditmemo as CreditmemoFixture;
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;
use Magento\SalesSequence\Model\Manager;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tilta\Payment\Gateway\RequestBuilder\Common\AddressBuilder;
use Tilta\Payment\Gateway\RequestBuilder\Common\AmountBuilder;
use Tilta\Payment\Gateway\RequestBuilder\Common\LineItemsBuilder;
use Tilta\Payment\Gateway\RequestBuilder\RefundInvoiceRequestBuilder;
use Tilta\Sdk\Model\Address;
use Tilta\Sdk\Model\Amount;
use Tilta\Sdk\Model\Order\LineItem;
use Tilta\Sdk\Model\Request\CreditNote\CreateCreditNoteRequestModel;

#[
    DataFixture(ProductFixture::class, as: 'product'),
    DataFixture(GuestCart::class, as: 'cart'),
    DataFixture(SetGuestEmail::class, ['cart_id' => '$cart.id$']),
    DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
    DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$', 'address' => ['company' => 'Test GmbH']]),
    DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
    DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
    DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
    DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
    DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
    DataFixture(CreditmemoFixture::class, ['order_id' => '$order.id$'], 'creditMemo'),
]
class RefundInvoiceRequestBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');
        /** @var CreditmemoInterface $creditMemo */
        $creditMemo = DataFixtureStorageManager::getStorage()->get('creditMemo');
        self::assertNotNull($order);
        self::assertNotNull($creditMemo);

        $orderAdapter = $objectManager->create(OrderAdapter::class, ['order' => $order]);
        $payment = $objectManager->create(Payment::class, [
            'data' => [
                'creditmemo' => $creditMemo,
            ],
        ]);
        $payment->setAdditionalInformation([
            'tilta_buyer_external_id' => 'test-buyer-id',
        ]);

        $buildSubject = ['payment' => new PaymentDataObject($orderAdapter, $payment)];
        $result = $this->getBuilder()->build($buildSubject);
        self::assertIsArray($result);
        self::assertIsArray($result);
        self::assertArrayHasKey('request_model', $result);
        self::assertInstanceOf(CreateCreditNoteRequestModel::class, $result['request_model']);
        /** @var CreateCreditNoteRequestModel $model */
        $model = $result['request_model'];

        self::assertEquals($creditMemo->getIncrementId(), $model->getCreditNoteExternalId());
        self::assertEquals([$order->getIncrementId()], $model->getOrderExternalIds());
        self::assertNotEmpty($model->getAmount());
        self::assertNotEmpty($model->getBillingAddress());
        self::assertCount(1, $model->getLineItems());
    }

    public function testIfExceptionIsThrownOnMissingCreditMemo(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');
        /** @var CreditmemoInterface $creditMemo */
        $creditMemo = DataFixtureStorageManager::getStorage()->get('creditMemo');
        self::assertNotNull($order);
        self::assertNotNull($creditMemo);

        $orderAdapter = $objectManager->create(OrderAdapter::class, ['order' => $order]);
        $payment = $objectManager->create(Payment::class);
        $payment->setAdditionalInformation([
            'tilta_buyer_external_id' => 'test-buyer-id',
        ]);

        $buildSubject = ['payment' => new PaymentDataObject($orderAdapter, $payment)];
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Credit memo was not found.');
        $this->getBuilderWithoutExpect()->build($buildSubject);
    }

    public function testIfExceptionIsThrownOnMissingBuyerExternalId(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');
        /** @var CreditmemoInterface $creditMemo */
        $creditMemo = DataFixtureStorageManager::getStorage()->get('creditMemo');
        self::assertNotNull($order);
        self::assertNotNull($creditMemo);

        $orderAdapter = $objectManager->create(OrderAdapter::class, ['order' => $order]);
        $payment = $objectManager->create(Payment::class, [
            'data' => [
                'creditmemo' => $creditMemo,
            ],
        ]);

        $buildSubject = ['payment' => new PaymentDataObject($orderAdapter, $payment)];
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Buyer External ID has not been set.');
        $this->getBuilderWithoutExpect()->build($buildSubject);
    }

    private function getBuilder(): RefundInvoiceRequestBuilder
    {
        $amountBuilder = $this->createMock(AmountBuilder::class);
        $amountBuilder->expects($this->atLeastOnce())->method('createForCreditMemo')->willReturn($this->createMock(Amount::class));
        $lineItemsBuilder = $this->createMock(LineItemsBuilder::class);
        $lineItemsBuilder->expects($this->atLeastOnce())->method('buildForCreditMemo')->willReturn([$this->createMock(LineItem::class)]);
        $addressBuilder = $this->createMock(AddressBuilder::class);
        $addressBuilder->expects($this->once())->method('buildForOrderAddress')->willReturn($this->createMock(Address::class));

        return new RefundInvoiceRequestBuilder(
            $this->createMock(Manager::class),
            $amountBuilder,
            $addressBuilder,
            $lineItemsBuilder,
        );
    }

    private function getBuilderWithoutExpect(): RefundInvoiceRequestBuilder
    {
        $amountBuilder = $this->createMock(AmountBuilder::class);
        $amountBuilder->method('createForCreditMemo')->willReturn($this->createMock(Amount::class));
        $lineItemsBuilder = $this->createMock(LineItemsBuilder::class);
        $lineItemsBuilder->method('buildForCreditMemo')->willReturn([$this->createMock(LineItem::class)]);
        $addressBuilder = $this->createMock(AddressBuilder::class);
        $addressBuilder->method('buildForOrderAddress')->willReturn($this->createMock(Address::class));

        return new RefundInvoiceRequestBuilder(
            $this->createMock(Manager::class),
            $amountBuilder,
            $addressBuilder,
            $lineItemsBuilder,
        );
    }
}
