<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Integration\Observer;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Payment\Model\Method\Adapter;
use Magento\Quote\Model\Quote;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class IsPaymentMethodAvailableFilterTest extends TestCase
{
    #[
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Customer::class, [
            CustomerInterface::KEY_ADDRESSES => [[AddressInterface::COMPANY => 'Test GmbH', AddressInterface::DEFAULT_BILLING => true]],
        ], as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
    ]
    public function testIsAvailable(): void
    {
        /** @var Quote $quote */
        $quote = DataFixtureStorageManager::getStorage()->get('cart');
        self::assertNotNull($quote);

        /** @var Adapter $paymentMethod */
        $paymentMethod = ObjectManager::getInstance()->get('TiltaPaymentFacade');
        self::assertInstanceOf(Adapter::class, $paymentMethod);

        self::assertTrue($paymentMethod->isAvailable($quote));
    }

    #[
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Customer::class, [CustomerInterface::KEY_ADDRESSES => [[AddressInterface::COMPANY => null, AddressInterface::DEFAULT_BILLING => true]]], as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
    ]
    public function testIsNotAvailableWithPrivateAddress(): void
    {
        /** @var Quote $quote */
        $quote = DataFixtureStorageManager::getStorage()->get('cart');
        self::assertNotNull($quote);

        /** @var Adapter $paymentMethod */
        $paymentMethod = ObjectManager::getInstance()->get('TiltaPaymentFacade');
        self::assertInstanceOf(Adapter::class, $paymentMethod);

        self::assertFalse($paymentMethod->isAvailable($quote), 'payment method should be not available, because customer is a B2C customer.');
    }

    #[
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart'),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$', 'address' => ['company' => 'Test GmbH']]),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
    ]
    public function testIsNotAvailableWithNewAddress(): void
    {
        /** @var Quote $quote */
        $quote = DataFixtureStorageManager::getStorage()->get('cart');
        self::assertNotNull($quote);

        /** @var Adapter $paymentMethod */
        $paymentMethod = ObjectManager::getInstance()->get('TiltaPaymentFacade');
        self::assertInstanceOf(Adapter::class, $paymentMethod);

        self::assertFalse($paymentMethod->isAvailable($quote), 'payment method should be not available, because customer does have a new address, which has not been saved to the address book.');
    }
}
