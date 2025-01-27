<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Unit\Observer;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Address;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tilta\Payment\Exception\CountryChangeIsNotAllowedException;
use Tilta\Payment\Observer\OnAddressSaveCountryValidation;
use Tilta\Payment\Service\BuyerService;

class OnAddressSaveCountryValidationTest extends TestCase
{
    public function testCanChangeCountryOnCountryChangeIfBuyerServiceDoesAllow(): void
    {
        $buyerService = $this->createMock(BuyerService::class);
        $observer = new OnAddressSaveCountryValidation($buyerService);

        $buyerService->expects($this->once())->method('canChangeCountry')->willReturn(true);

        $address = $this->getMockedAddress([
            AddressInterface::COUNTRY_ID => 'DE',
        ]);
        $address->setData(AddressInterface::COUNTRY_ID, 'US');

        $observer->execute(new Observer([
            'data_object' => $address,
        ]));
        self::assertTrue(true);
    }

    public function testCanChangeCountryOnNoChange(): void
    {
        $buyerService = $this->createMock(BuyerService::class);
        $observer = new OnAddressSaveCountryValidation($buyerService);

        $buyerService->expects($this->never())->method('canChangeCountry');

        $address = $this->getMockedAddress([
            AddressInterface::COUNTRY_ID => 'DE',
        ]);
        $address->setData(AddressInterface::FIRSTNAME, 'first name');

        $observer->execute(new Observer([
            'data_object' => $address,
        ]));
        self::assertTrue(true);
    }

    public function testCanNotChangeCountryIfBuyerServiceDoNotAllow(): void
    {
        $buyerService = $this->createMock(BuyerService::class);
        $observer = new OnAddressSaveCountryValidation($buyerService);

        $buyerService->expects($this->once())->method('canChangeCountry')->willReturn(false);
        $this->expectException(CountryChangeIsNotAllowedException::class);

        $address = $this->getMockedAddress([
            AddressInterface::COUNTRY_ID => 'DE',
        ]);
        $address->setData(AddressInterface::COUNTRY_ID, 'US');

        $observer->execute(new Observer([
            'data_object' => $address,
        ]));
    }

    private function getMockedAddress(array $data): Address
    {
        /** @var Address $address */
        $address = (new ObjectManager($this))->getObject(Address::class, [
            'data' => array_merge($data, [
                'id' => '12345',
            ]),
        ]);
        $address->setOrigData();

        return $address;
    }
}
