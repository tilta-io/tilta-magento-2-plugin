<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Integration\Gateway\RequestBuilder\Common;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart;
use Magento\Sales\Model\Order;
use Magento\Sales\Test\Fixture\Creditmemo as CreditmemoFixture;
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Test\Fixture\Rule as RuleFixture;
use Magento\Tax\Test\Fixture\TaxRate as TaxRateFixture;
use Magento\Tax\Test\Fixture\TaxRule as TaxRuleFixture;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tilta\Payment\Gateway\RequestBuilder\Common\AmountBuilder;
use Tilta\Sdk\Model\Amount;

#[AppIsolation(true)]
class AmountBuilderTest extends TestCase
{
    private const SHIPPING_PRICE_NET = 5;

    private const PRODUCT_PRICE_NET = 135;

    private const DISCOUNT_PRICE = 10;

    #[
        ConfigFixture('currency/options/base', 'EUR'),
        ConfigFixture('tax/classes/shipping_tax_class', 2, 'store', 'default'),
        ConfigFixture('carriers/flatrate/type', 'O', 'store', 'default'),
        ConfigFixture('carriers/flatrate/price', self::SHIPPING_PRICE_NET, 'store', 'default'),
        DataFixture(TaxRateFixture::class, ['rate' => 19], 'rate'),
        DataFixture(TaxRuleFixture::class, ['customer_tax_class_ids' => [3], 'product_tax_class_ids' => [2], 'tax_rate_ids' => ['$rate.id$']]),
        DataFixture(ProductFixture::class, ['price' => self::PRODUCT_PRICE_NET], as: 'product'),
        DataFixture(GuestCart::class, as: 'cart'),
        DataFixture(SetGuestEmail::class, ['cart_id' => '$cart.id$']),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(CreditmemoFixture::class, ['order_id' => '$order.id$'], 'creditMemo'),
    ]
    public function testBuildWithTaxAndShipping(): void
    {
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');

        self::assertEquals(self::PRODUCT_PRICE_NET * 2, $order->getBaseSubtotal());
        self::assertEquals(self::PRODUCT_PRICE_NET * 2 * 1.19, $order->getBaseSubtotalInclTax());
        self::assertEquals(self::SHIPPING_PRICE_NET, $order->getBaseShippingAmount());
        self::assertGreaterThan(0.0, (float) $order->getTaxAmount());

        $expectedTotalAmountNet =
            self::PRODUCT_PRICE_NET * 2
            + self::SHIPPING_PRICE_NET;

        self::assertAmount($expectedTotalAmountNet, 19);
    }

    #[
        ConfigFixture('currency/options/base', 'EUR'),
        ConfigFixture('tax/classes/shipping_tax_class', 2, 'store', 'default'),
        ConfigFixture('carriers/flatrate/type', 'O', 'store', 'default'),
        ConfigFixture('carriers/flatrate/price', self::SHIPPING_PRICE_NET, 'store', 'default'),
        DataFixture(TaxRateFixture::class, ['rate' => 19], 'rate'),
        DataFixture(TaxRuleFixture::class, ['customer_tax_class_ids' => [3], 'product_tax_class_ids' => [2], 'tax_rate_ids' => ['$rate.id$']]),
        DataFixture(ProductFixture::class, ['price' => self::PRODUCT_PRICE_NET], as: 'product'),
        DataFixture(
            RuleFixture::class,
            [
                'simple_action' => Rule::CART_FIXED_ACTION,
                'discount_amount' => self::DISCOUNT_PRICE,
                'apply_to_shipping' => 0,
                'stop_rules_processing' => 0,
                'sort_order' => 1,
                'discount_qty' => 1,
            ]
        ),
        DataFixture(GuestCart::class, as: 'cart'),
        DataFixture(SetGuestEmail::class, ['cart_id' => '$cart.id$']),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(CreditmemoFixture::class, ['order_id' => '$order.id$'], 'creditMemo'),
    ]
    public function testBuildWithTaxWithShippingAndDiscount(): void
    {
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');

        self::assertEquals(self::PRODUCT_PRICE_NET * 2, $order->getBaseSubtotal());
        self::assertEquals(self::PRODUCT_PRICE_NET * 1.19 * 2, $order->getBaseSubtotalInclTax());
        self::assertEquals(self::SHIPPING_PRICE_NET, $order->getBaseShippingAmount());
        self::assertEquals(-self::DISCOUNT_PRICE, $order->getBaseDiscountAmount());
        self::assertGreaterThan(0.0, (float) $order->getTaxAmount());
        self::assertGreaterThan(0.0, (float) $order->getShippingTaxAmount());

        $expectedTotalAmountNet =
            (self::PRODUCT_PRICE_NET * 2)
            + self::SHIPPING_PRICE_NET
            - self::DISCOUNT_PRICE;

        self::assertAmount($expectedTotalAmountNet, 19);
    }

    #[
        ConfigFixture('currency/options/base', 'EUR'),
        ConfigFixture('carriers/flatrate/type', 'O', 'store', 'default'),
        ConfigFixture('carriers/flatrate/price', self::SHIPPING_PRICE_NET, 'store', 'default'),
        DataFixture(TaxRateFixture::class, ['rate' => 0], 'rate'),
        DataFixture(TaxRuleFixture::class, ['customer_tax_class_ids' => [3], 'product_tax_class_ids' => [2], 'tax_rate_ids' => ['$rate.id$']]),
        DataFixture(ProductFixture::class, ['price' => self::PRODUCT_PRICE_NET], as: 'product'),
        DataFixture(
            RuleFixture::class,
            [
                'simple_action' => Rule::CART_FIXED_ACTION,
                'discount_amount' => self::DISCOUNT_PRICE,
                'apply_to_shipping' => 0,
                'stop_rules_processing' => 0,
                'sort_order' => 1,
                'discount_qty' => 1,
            ]
        ),
        DataFixture(GuestCart::class, as: 'cart'),
        DataFixture(SetGuestEmail::class, ['cart_id' => '$cart.id$']),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(CreditmemoFixture::class, ['order_id' => '$order.id$'], 'creditMemo'),
    ]
    public function testBuildWithoutTaxWithShippingAndDiscount(): void
    {
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');

        self::assertEquals(self::PRODUCT_PRICE_NET * 2, $order->getBaseSubtotal());
        self::assertEquals(self::PRODUCT_PRICE_NET * 2, $order->getBaseSubtotalInclTax());
        self::assertEquals(self::SHIPPING_PRICE_NET, $order->getBaseShippingAmount());
        self::assertEquals(0.0, (float) $order->getTaxAmount());

        $expectedTotalAmountNet =
            (self::PRODUCT_PRICE_NET * 2)
            + self::SHIPPING_PRICE_NET
            - self::DISCOUNT_PRICE;

        self::assertAmount($expectedTotalAmountNet, 0);
    }

    private static function assertAmount(float $expectedNet, float $taxRate = 19, Amount $amount = null): void
    {
        if (!$amount instanceof Amount) {
            /** @var Quote $cart */
            $cart = DataFixtureStorageManager::getStorage()->get('cart');
            $cart = ObjectManager::getInstance()->get(CartRepositoryInterface::class)->get((int) $cart->getId());
            self::assertAmount($expectedNet, $taxRate, (new AmountBuilder())->createForCart($cart));
            /** @var Order $order */
            $order = DataFixtureStorageManager::getStorage()->get('order');
            self::assertAmount($expectedNet, $taxRate, (new AmountBuilder())->createForOrder($order));
            /** @var Order\Invoice $invoice */
            $invoice = DataFixtureStorageManager::getStorage()->get('invoice');
            self::assertAmount($expectedNet, $taxRate, (new AmountBuilder())->createForInvoice($invoice));
            /** @var Order\Creditmemo $creditMemo */
            $creditMemo = DataFixtureStorageManager::getStorage()->get('creditMemo');
            self::assertAmount($expectedNet, $taxRate, (new AmountBuilder())->createForCreditMemo($creditMemo));
            return;
        }

        if ($taxRate !== null) {
            $taxRate = $taxRate / 100 + 1;
            self::assertEquals(round($expectedNet * $taxRate, 2), $amount->getGross() / 100);
        } else {
            self::assertEquals($expectedNet, $amount->getGross() / 100);
        }

        self::assertEquals($expectedNet, $amount->getNet() / 100);
        self::assertNull($amount->getTax(), 'tax should be null to prevent rounding issues');
        self::assertEquals('EUR', $amount->getCurrency());
    }
}
