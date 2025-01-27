<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Integration\Model;

use DateTime;
use Exception;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tilta\Payment\Api\Data\CheckoutPaymentTermInterface;
use Tilta\Payment\Api\Data\CheckoutPaymentTermsResponseInterface;
use Tilta\Payment\Model\CheckoutPaymentTerms;
use Tilta\Payment\Service\FacilityService;
use Tilta\Payment\Service\PaymentTermsService;
use Tilta\Sdk\Exception\GatewayException\Facility\FacilityExceededException;
use Tilta\Sdk\Model\Response\Facility\GetFacilityResponseModel;
use Tilta\Sdk\Model\Response\PaymentTerm\GetPaymentTermsResponseModel;
use Tilta\Sdk\Util\ResponseHelper;

#[
    DataFixture(ProductFixture::class, as: 'product'),
    DataFixture(GuestCart::class, as: 'cart'),
    DataFixture(SetGuestEmail::class, ['cart_id' => '$cart.id$']),
    DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]),
]
class CheckoutPaymentTermsTest extends TestCase
{
    public function testGetPaymentTermsForCart(): void
    {
        $objectManager = ObjectManager::getInstance();
        $paymentTermsService = $this->createMock(PaymentTermsService::class);
        $facilityService = $this->createMock(FacilityService::class);
        $addressRepo = $this->createMock(AddressRepositoryInterface::class);
        $addressRepo->method('getById')->willReturn($this->createMock(AddressInterface::class));
        $service = (new CheckoutPaymentTerms(
            $objectManager->get(CartRepositoryInterface::class),
            $objectManager,
            $paymentTermsService,
            $facilityService,
            $addressRepo,
            $this->createMock(LoggerInterface::class)
        ));

        /** @var Quote $cart */
        $cart = DataFixtureStorageManager::getStorage()->get('cart');

        $paymentTermsService->method('getPaymentTermsForQuote')->willReturn((new GetPaymentTermsResponseModel([
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
        ])));

        $paymentTerms = $service->getPaymentTermsForCart(999, (int) $cart->getId());
        self::assertInstanceOf(CheckoutPaymentTermsResponseInterface::class, $paymentTerms);
        self::assertFalse($paymentTerms->isAllowCreateFacility());
        self::assertNull($paymentTerms->getErrorMessage());
        self::assertCount(2, $paymentTerms->getPaymentTerms());
        self::assertContainsOnlyInstancesOf(CheckoutPaymentTermInterface::class, $paymentTerms->getPaymentTerms());
        /** @var CheckoutPaymentTermInterface $term */
        $term = $paymentTerms->getPaymentTerms()[0];
        self::assertEquals('test-name', $term->getName());
        self::assertEquals('test-payment-term', $term->getPaymentTerm());
        self::assertEquals('test-payment-method', $term->getPaymentMethod());
        self::assertEquals('2050-01-01', $term->getDueDate());

        /** @var CheckoutPaymentTermInterface $term */
        $term = $paymentTerms->getPaymentTerms()[1];
        self::assertEquals('test-name2', $term->getName());
        self::assertEquals('test-payment-term2', $term->getPaymentTerm());
        self::assertEquals('test-payment-method2', $term->getPaymentMethod());
        self::assertEquals('2100-01-01', $term->getDueDate());
    }

    public function testFacilityIsToLow(): void
    {
        $objectManager = ObjectManager::getInstance();
        $paymentTermsService = $this->createMock(PaymentTermsService::class);
        $facilityService = $this->createMock(FacilityService::class);
        $addressRepo = $this->createMock(AddressRepositoryInterface::class);
        $addressRepo->method('getById')->willReturn($this->createMock(AddressInterface::class));
        $service = (new CheckoutPaymentTerms(
            $objectManager->get(CartRepositoryInterface::class),
            $objectManager,
            $paymentTermsService,
            $facilityService,
            $addressRepo,
            $this->createMock(LoggerInterface::class)
        ));

        /** @var Quote $cart */
        $cart = DataFixtureStorageManager::getStorage()->get('cart');

        $facilityService->method('getFacility')->willReturn((new GetFacilityResponseModel([
            'status' => 'OK',
            'expires_at' => (new DateTime())->modify('+1 day')->format('U'),
            'currency' => 'EUR',
            'total_amount' => 1,
            'available_amount' => 1,
            'used_amount' => 0,
            'pending_orders_amount' => 0,
            'buyer_external_id' => 'test-buyer-id',
        ])));

        $paymentTermsService->method('getPaymentTermsForQuote')->willThrowException(new FacilityExceededException('test', 132));

        $paymentTerms = $service->getPaymentTermsForCart(999, (int) $cart->getId());
        self::assertInstanceOf(CheckoutPaymentTermsResponseInterface::class, $paymentTerms);
        self::assertFalse($paymentTerms->isAllowCreateFacility());
        self::assertStringContainsString('Your credit limit does not cover the total of this order.', $paymentTerms->getErrorMessage());
        self::assertCount(0, $paymentTerms->getPaymentTerms());
    }

    public function testFacilityAlreadyUsed(): void
    {
        $objectManager = ObjectManager::getInstance();
        $paymentTermsService = $this->createMock(PaymentTermsService::class);
        $facilityService = $this->createMock(FacilityService::class);
        $addressRepo = $this->createMock(AddressRepositoryInterface::class);
        $addressRepo->method('getById')->willReturn($this->createMock(AddressInterface::class));
        $service = (new CheckoutPaymentTerms(
            $objectManager->get(CartRepositoryInterface::class),
            $objectManager,
            $paymentTermsService,
            $facilityService,
            $addressRepo,
            $this->createMock(LoggerInterface::class)
        ));

        /** @var Quote $cart */
        $cart = DataFixtureStorageManager::getStorage()->get('cart');

        $facilityService->method('getFacility')->willReturn((new GetFacilityResponseModel([
            'status' => 'OK',
            'expires_at' => (new DateTime())->modify('+1 day')->format('U'),
            'currency' => 'EUR',
            'total_amount' => 99999,
            'available_amount' => 0,
            'used_amount' => 99999,
            'pending_orders_amount' => 99999,
            'buyer_external_id' => 'test-buyer-id',
        ])));

        $paymentTermsService->method('getPaymentTermsForQuote')->willThrowException(new FacilityExceededException('test', 132));

        $paymentTerms = $service->getPaymentTermsForCart(999, (int) $cart->getId());
        self::assertInstanceOf(CheckoutPaymentTermsResponseInterface::class, $paymentTerms);
        self::assertFalse($paymentTerms->isAllowCreateFacility());
        self::assertStringContainsString('Your credit limit is currently reached', $paymentTerms->getErrorMessage());
        self::assertCount(0, $paymentTerms->getPaymentTerms());
    }

    public function testUnexpectedException(): void
    {
        $objectManager = ObjectManager::getInstance();
        $paymentTermsService = $this->createMock(PaymentTermsService::class);
        $facilityService = $this->createMock(FacilityService::class);
        $addressRepo = $this->createMock(AddressRepositoryInterface::class);
        $addressRepo->method('getById')->willReturn($this->createMock(AddressInterface::class));
        $service = (new CheckoutPaymentTerms(
            $objectManager->get(CartRepositoryInterface::class),
            $objectManager,
            $paymentTermsService,
            $facilityService,
            $addressRepo,
            $this->createMock(LoggerInterface::class)
        ));

        /** @var Quote $cart */
        $cart = DataFixtureStorageManager::getStorage()->get('cart');

        $paymentTermsService->method('getPaymentTermsForQuote')->willThrowException(new Exception());

        $paymentTerms = $service->getPaymentTermsForCart(999, (int) $cart->getId());
        self::assertInstanceOf(CheckoutPaymentTermsResponseInterface::class, $paymentTerms);
        self::assertFalse($paymentTerms->isAllowCreateFacility());
        self::assertStringContainsString('you cannot use this payment method', $paymentTerms->getErrorMessage());
        self::assertCount(0, $paymentTerms->getPaymentTerms());
    }

    public function testNoFacility(): void
    {
        $objectManager = ObjectManager::getInstance();
        $paymentTermsService = $this->createMock(PaymentTermsService::class);
        $facilityService = $this->createMock(FacilityService::class);
        $addressRepo = $this->createMock(AddressRepositoryInterface::class);
        $addressRepo->method('getById')->willReturn($this->createMock(AddressInterface::class));
        $service = (new CheckoutPaymentTerms(
            $objectManager->get(CartRepositoryInterface::class),
            $objectManager,
            $paymentTermsService,
            $facilityService,
            $addressRepo,
            $this->createMock(LoggerInterface::class)
        ));

        /** @var Quote $cart */
        $cart = DataFixtureStorageManager::getStorage()->get('cart');

        $paymentTermsService->method('getPaymentTermsForQuote')->willReturn(null);

        $paymentTerms = $service->getPaymentTermsForCart(999, (int) $cart->getId());
        self::assertInstanceOf(CheckoutPaymentTermsResponseInterface::class, $paymentTerms);
        self::assertTrue($paymentTerms->isAllowCreateFacility());
        self::assertNull($paymentTerms->getErrorMessage());
        self::assertCount(0, $paymentTerms->getPaymentTerms());
    }
}
