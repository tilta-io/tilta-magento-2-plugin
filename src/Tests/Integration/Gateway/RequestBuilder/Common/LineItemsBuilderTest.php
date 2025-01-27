<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Integration\Gateway\RequestBuilder\Common;

use Magento\Bundle\Test\Fixture\AddProductToCart as AddBundleProductToCartFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\ConfigurableProduct\Test\Fixture\AddProductToCart as AddConfigurableProductToCartFixture;
use Magento\ConfigurableProduct\Test\Fixture\Attribute as AttributeFixture;
use Magento\ConfigurableProduct\Test\Fixture\Product as ConfigurableProductFixture;
use Magento\GroupedProduct\Test\Fixture\AddProductToCart as AddGroupedProductToCartFixture;
use Magento\GroupedProduct\Test\Fixture\Product as GroupedProductFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart;
use Magento\Sales\Model\Order;
use Magento\Sales\Test\Fixture\Creditmemo as CreditmemoFixture;
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;
use Magento\Tax\Test\Fixture\TaxRate as TaxRateFixture;
use Magento\Tax\Test\Fixture\TaxRule as TaxRuleFixture;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tilta\Payment\Gateway\RequestBuilder\Common\LineItemsBuilder;
use Tilta\Sdk\Model\Order\LineItem;

#[AppIsolation(true)]
class LineItemsBuilderTest extends TestCase
{
    #[
        ConfigFixture('currency/options/base', 'EUR'),
        DataFixture(TaxRateFixture::class, ['rate' => 19], 'rate'),
        DataFixture(TaxRuleFixture::class, ['customer_tax_class_ids' => [3], 'product_tax_class_ids' => [2], 'tax_rate_ids' => ['$rate.id$']]),
        DataFixture(ProductFixture::class, [
            'price' => 100,
            'sku' => 'test-sku',
            'name' => 'test-name',
        ], as: 'product'),
        DataFixture(ProductFixture::class, [
            'price' => 30,
            'sku' => 'test-sku2',
            'name' => 'test-name2',
        ], as: 'product2'),
        DataFixture(GuestCart::class, as: 'cart'),
        DataFixture(SetGuestEmail::class, ['cart_id' => '$cart.id$']),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 4]),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product2.id$', 'qty' => 1]),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(CreditmemoFixture::class, ['order_id' => '$order.id$'], 'creditMemo'),
    ]
    public function testBuildSimpleProduct(): void
    {
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');

        // pre-check if order has been created as expected
        self::assertCount(2, $order->getItems(), 'order has not been created as expected.');

        self::assertList(function (array $list, string $messagePrefix): void {
            self::assertCount(2, $list);
            /** @var LineItem $item */
            $item = $list[0];
            self::assertEquals('test-name', $item->getName(), $messagePrefix);
            self::assertEquals(4, $item->getQuantity(), $messagePrefix);
            self::assertEquals(round(100 * 1.19, 2), $item->getPrice() / 100, $messagePrefix);
            self::assertEquals('EUR', $item->getCurrency(), $messagePrefix);

            /** @var LineItem $item */
            $item = $list[1];
            self::assertEquals('test-name2', $item->getName(), $messagePrefix);
            self::assertEquals(1, $item->getQuantity(), $messagePrefix);
            self::assertEquals(round(30 * 1.19, 2), $item->getPrice() / 100, $messagePrefix);
            self::assertEquals('EUR', $item->getCurrency(), $messagePrefix);
        });
    }

    #[
        ConfigFixture('currency/options/base', 'EUR'),
        DataFixture(TaxRateFixture::class, ['rate' => 19], 'rate'),
        DataFixture(TaxRuleFixture::class, ['customer_tax_class_ids' => [3], 'product_tax_class_ids' => [2], 'tax_rate_ids' => ['$rate.id$']]),
        DataFixture(ProductFixture::class, ['name' => 'test-name 1', 'price' => 123], as: 'product1'),
        DataFixture(ProductFixture::class, ['name' => 'test-name 2', 'price' => 456], as: 'product2'),
        DataFixture(ProductFixture::class, ['name' => 'test-name 3', 'price' => 789], as: 'product3'),
        DataFixture(ProductFixture::class, ['name' => 'test-name 4', 'price' => 987], as: 'product4'),
        DataFixture(AttributeFixture::class, as: 'attr'),
        DataFixture(
            ConfigurableProductFixture::class,
            ['_options' => ['$attr$'], '_links' => ['$product1$', '$product2$']],
            'configurableProduct1'
        ),
        DataFixture(
            ConfigurableProductFixture::class,
            ['_options' => ['$attr$'], '_links' => ['$product3$', '$product4$']],
            'configurableProduct2'
        ),
        DataFixture(GuestCart::class, as: 'cart'),
        DataFixture(
            AddConfigurableProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$configurableProduct1.id$', 'child_product_id' => '$product2.id$', 'qty' => 4],
        ),
        DataFixture(
            AddConfigurableProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$configurableProduct2.id$', 'child_product_id' => '$product3.id$', 'qty' => 2],
        ),
        DataFixture(SetGuestEmail::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(CreditmemoFixture::class, ['order_id' => '$order.id$'], 'creditMemo'),
    ]
    public function testBuildConfigurableProduct(): void
    {
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');

        // pre-check if order has been created as expected
        // magento creates 2 line-items for 1 configurable product
        self::assertCount(4, $order->getItems(), 'order has not been created as expected.');

        self::assertList(function (array $list, string $messagePrefix): void {
            self::assertCount(2, $list, $messagePrefix . 'there should be only 2 items');
            /** @var LineItem $item */
            $item = $list[0];
            self::assertEquals('test-name 2', $item->getName(), $messagePrefix);
            self::assertEquals(4, $item->getQuantity(), $messagePrefix);
            self::assertEquals(round(456 * 1.19, 2), $item->getPrice() / 100, $messagePrefix);
            self::assertEquals('EUR', $item->getCurrency(), $messagePrefix);

            /** @var LineItem $item */
            $item = $list[1];
            self::assertEquals('test-name 3', $item->getName(), $messagePrefix);
            self::assertEquals(2, $item->getQuantity(), $messagePrefix);
            self::assertEquals(round(789 * 1.19, 2), $item->getPrice() / 100, $messagePrefix);
            self::assertEquals('EUR', $item->getCurrency(), $messagePrefix);
        });
    }

    private static function assertList(callable $callable, array $list = null, string $testType = null): void
    {
        if ($list === null) {
            $builder = ObjectManager::getInstance()->get(LineItemsBuilder::class);
            /** @var Order $order */
            $order = DataFixtureStorageManager::getStorage()->get('order');
            self::assertList($callable, $builder->buildForOrder($order), 'Order');
            /** @var Order\Invoice $invoice */
            $invoice = DataFixtureStorageManager::getStorage()->get('invoice');
            self::assertList($callable, $builder->buildForInvoice($invoice), 'Invoice');
            /** @var Order\Creditmemo $creditMemo */
            $creditMemo = DataFixtureStorageManager::getStorage()->get('creditMemo');
            self::assertList($callable, $builder->buildForCreditMemo($creditMemo), 'CreditMemo');

            return;
        }

        self::assertContainsOnlyInstancesOf(LineItem::class, $list);
        $callable($list, sprintf('Failed to test %s: ', $testType));
    }

    #[
        ConfigFixture('currency/options/base', 'EUR'),
        DataFixture(TaxRateFixture::class, ['rate' => 19], 'rate'),
        DataFixture(TaxRuleFixture::class, ['customer_tax_class_ids' => [3], 'product_tax_class_ids' => [2], 'tax_rate_ids' => ['$rate.id$']]),

        // bundle-product #1
        DataFixture(ProductFixture::class, ['name' => 'test-name 1', 'price' => 123], as: 'product1'),
        DataFixture(ProductFixture::class, ['name' => 'test-name 2', 'price' => 456], as: 'product2'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$product1$', '$product2$']], 'bundleOption1'),
        DataFixture(BundleProductFixture::class, ['_options' => ['$bundleOption1$']], 'bundleProduct1'),

        // bundle-product #2
        DataFixture(ProductFixture::class, ['name' => 'test-name 3', 'price' => 789], as: 'product3'),
        DataFixture(ProductFixture::class, ['name' => 'test-name 4', 'price' => 987], as: 'product4'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$product3$', '$product4$']], 'bundleOption2'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$product3$', '$product4$']], 'bundleOption2_2'),
        DataFixture(BundleProductFixture::class, ['_options' => ['$bundleOption2$', '$bundleOption2_2$']], 'bundleProduct2'),

        DataFixture(GuestCart::class, as: 'cart'),
        DataFixture(AddBundleProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$bundleProduct1.id$', 'selections' => [['$product2.id$']], 'qty' => 2]),
        DataFixture(AddBundleProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$bundleProduct2.id$', 'selections' => [['$product3.id$'], ['$product4.id$']], 'qty' => 5]),

        DataFixture(SetGuestEmail::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(CreditmemoFixture::class, ['order_id' => '$order.id$'], 'creditMemo'),
    ]
    public function testBuildBundleProduct(): void
    {
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');

        // pre-check if order has been created as expected
        self::assertCount(5, $order->getItems(), 'order has not been created as expected.');

        self::assertList(function (array $list, string $messagePrefix): void {
            self::assertCount(3, $list, $messagePrefix);

            // products of first bundle
            /** @var LineItem $item */
            $item = $list[0];
            self::assertEquals('test-name 2', $item->getName(), $messagePrefix);
            self::assertEquals(2, $item->getQuantity(), $messagePrefix);
            self::assertEquals(round(456 * 1.19, 2), $item->getPrice() / 100, $messagePrefix);
            self::assertEquals('EUR', $item->getCurrency(), $messagePrefix);

            // products of second bundle
            /** @var LineItem $item */
            $item = $list[1];
            self::assertEquals('test-name 3', $item->getName(), $messagePrefix);
            self::assertEquals(5, $item->getQuantity(), $messagePrefix);
            self::assertEquals(round(789 * 1.19, 2), $item->getPrice() / 100, $messagePrefix);
            self::assertEquals('EUR', $item->getCurrency(), $messagePrefix);
            /** @var LineItem $item */
            $item = $list[2];
            self::assertEquals('test-name 4', $item->getName(), $messagePrefix);
            self::assertEquals(5, $item->getQuantity(), $messagePrefix);
            self::assertEquals(round(987 * 1.19, 2), $item->getPrice() / 100, $messagePrefix);
            self::assertEquals('EUR', $item->getCurrency(), $messagePrefix);
        });
    }

    #[
        ConfigFixture('currency/options/base', 'EUR'),
        DataFixture(TaxRateFixture::class, ['rate' => 19], 'rate'),
        DataFixture(TaxRuleFixture::class, ['customer_tax_class_ids' => [3], 'product_tax_class_ids' => [2], 'tax_rate_ids' => ['$rate.id$']]),

        // grouped-product #1
        DataFixture(ProductFixture::class, ['name' => 'test-name 1', 'price' => 123], as: 'product1'),
        DataFixture(ProductFixture::class, ['name' => 'test-name 2', 'price' => 456], as: 'product2'),
        DataFixture(GroupedProductFixture::class, ['product_links' => ['$product1$', '$product2$']], 'groupedProduct1'),
        // grouped-product #2
        DataFixture(ProductFixture::class, ['name' => 'test-name 3', 'price' => 789], as: 'product3'),
        DataFixture(ProductFixture::class, ['name' => 'test-name 4', 'price' => 987], as: 'product4'),
        DataFixture(GroupedProductFixture::class, ['product_links' => ['$product3$', '$product4$']], 'groupedProduct2'),

        DataFixture(GuestCart::class, as: 'cart'),
        DataFixture(AddGroupedProductToCartFixture::class, [
            'cart_id' => '$cart.id$',
            'product_id' => '$groupedProduct1.id$',
            'child_products' => [
                ['product_id' => '$product1.id$', 'qty' => 3],
                ['product_id' => '$product2.id$', 'qty' => 6],
            ],
        ]),
        DataFixture(AddGroupedProductToCartFixture::class, [
            'cart_id' => '$cart.id$',
            'product_id' => '$groupedProduct2.id$',
            'child_products' => [
                ['product_id' => '$product3.id$', 'qty' => 1],
                ['product_id' => '$product4.id$', 'qty' => 2],
            ],
        ]),

        DataFixture(SetGuestEmail::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(CreditmemoFixture::class, ['order_id' => '$order.id$'], 'creditMemo'),
    ]
    public function testBuildGroupedProduct(): void
    {
        /** @var Order $order */
        $order = DataFixtureStorageManager::getStorage()->get('order');

        // pre-check if order has been created as expected
        self::assertCount(4, $order->getItems(), 'order has not been created as expected.');

        self::assertList(function (array $list, string $messagePrefix): void {
            self::assertCount(4, $list, $messagePrefix);

            // products of first grouped
            /** @var LineItem $item */
            $item = $list[0];
            self::assertEquals('test-name 1', $item->getName(), $messagePrefix);
            self::assertEquals(3, $item->getQuantity(), $messagePrefix);
            self::assertEquals(round(123 * 1.19, 2), $item->getPrice() / 100, $messagePrefix);
            self::assertEquals('EUR', $item->getCurrency(), $messagePrefix);
            /** @var LineItem $item */
            $item = $list[1];
            self::assertEquals('test-name 2', $item->getName(), $messagePrefix);
            self::assertEquals(6, $item->getQuantity(), $messagePrefix);
            self::assertEquals(round(456 * 1.19, 2), $item->getPrice() / 100, $messagePrefix);
            self::assertEquals('EUR', $item->getCurrency(), $messagePrefix);

            // products of second grouped
            /** @var LineItem $item */
            $item = $list[2];
            self::assertEquals('test-name 3', $item->getName(), $messagePrefix);
            self::assertEquals(1, $item->getQuantity(), $messagePrefix);
            self::assertEquals(round(789 * 1.19, 2), $item->getPrice() / 100, $messagePrefix);
            self::assertEquals('EUR', $item->getCurrency(), $messagePrefix);
            /** @var LineItem $item */
            $item = $list[3];
            self::assertEquals('test-name 4', $item->getName(), $messagePrefix);
            self::assertEquals(2, $item->getQuantity(), $messagePrefix);
            self::assertEquals(round(987 * 1.19, 2), $item->getPrice() / 100, $messagePrefix);
            self::assertEquals('EUR', $item->getCurrency(), $messagePrefix);
        });
    }
}
