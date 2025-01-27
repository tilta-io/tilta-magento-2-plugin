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
use Magento\Payment\Gateway\Data\Order\OrderAdapter;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tilta\Payment\Gateway\RequestBuilder\CancelOrderRequestBuilder;
use Tilta\Sdk\Model\Request\Order\CancelOrderRequestModel;

class CancelOrderRequestBuilderTest extends TestCase
{
    #[
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(GuestCart::class, as: 'cart'),
        DataFixture(SetGuestEmail::class, ['cart_id' => '$cart.id$']),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order')
    ]
    public function testBuild(): void
    {
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');
        self::assertNotNull($order);

        $orderAdapter = ObjectManager::getInstance()->create(OrderAdapter::class, ['order' => $order]);
        $buildSubject = ['payment' => new PaymentDataObject($orderAdapter, $this->createMock(InfoInterface::class))];
        $result = (new CancelOrderRequestBuilder())->build($buildSubject);

        self::assertIsArray($result);
        self::assertArrayHasKey('request_model', $result);
        self::assertInstanceof(CancelOrderRequestModel::class, $result['request_model']);
        self::assertEquals($order->getIncrementId(), $result['request_model']->getOrderExternalId());
    }
}
