<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Unit\Gateway\RequestBuilder\Common;

use Magento\Sales\Api\Data\OrderAddressInterface;
use PHPUnit\Framework\TestCase;
use Tilta\Payment\Gateway\RequestBuilder\Common\AddressBuilder;

class AddressBuilderTest extends TestCase
{
    public function testBuilderForOrderAddress(): void
    {
        $address = $this->createMock(OrderAddressInterface::class);
        $address->method('getFirstname')->willReturn('John');
        $address->method('getLastname')->willReturn('Doe');
        $address->method('getCompany')->willReturn('Test Company');
        $address->method('getStreet')->willReturn(['Test Street 1a', 'add1', 'add2']);
        $address->method('getCity')->willReturn('Test City');
        $address->method('getPostcode')->willReturn('12345');
        $address->method('getCountryId')->willReturn('DE');

        $result = (new AddressBuilder())->buildForOrderAddress($address);

        self::assertEquals('Test Street', $result->getStreet());
        self::assertEquals('1a', $result->getHouseNumber());
        self::assertEquals('Test City', $result->getCity());
        self::assertEquals('DE', $result->getCountry());
        self::assertEquals('12345', $result->getPostcode());
        self::assertEquals("add1\nadd2", $result->getAdditional());
    }

    public function testNotFailOnMissingValues(): void
    {
        // just mock the interface, so all getters will return null. We want to make sure, that not fatal error occurs, if a value is set to null (magento does not have type-safe return annotations)
        $address = $this->createMock(OrderAddressInterface::class);

        $address->method('getCountryId')->willReturn('DE'); // country-id is always given, and does have additional validations
        $result = (new AddressBuilder())->buildForOrderAddress($address);

        self::assertNotNull($result);
    }
}
