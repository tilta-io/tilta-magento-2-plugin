<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Integration\Gateway\ResponseHandler;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Payment\Gateway\Data\Order\OrderAdapter;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart;
use Magento\Sales\Model\Order\Payment;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tilta\Payment\Gateway\ResponseHandler\CreateOrderResponseHandler;
use Tilta\Sdk\Model\Order;

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
class CreateOrderResponseHandlerTest extends TestCase
{
    public function testHandle(): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var \Magento\Sales\Model\Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');
        self::assertNotNull($order);

        $response = $this->createMock(Order::class);
        $response->method('getBuyerExternalId')->willReturn('test-buyer-id');

        $orderAdapter = $objectManager->create(OrderAdapter::class, ['order' => $order]);
        $payment = $objectManager->create(Payment::class);
        $buildSubject = ['payment' => new PaymentDataObject($orderAdapter, $payment)];
        (new CreateOrderResponseHandler())->handle($buildSubject, $response);

        self::assertEquals($order->getIncrementId(), $payment->getTransactionId());
        self::assertFalse($payment->getData('is_transaction_closed'));
        self::assertArrayHasKey('tilta_buyer_external_id', $payment->getAdditionalInformation());
        self::assertEquals('test-buyer-id', $payment->getAdditionalInformation('tilta_buyer_external_id'));
    }
}
