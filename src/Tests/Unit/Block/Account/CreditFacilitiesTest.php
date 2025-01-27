<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Unit\Block\Account;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Url;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\TestCase;
use Tilta\Payment\Block\Account\CreditFacilities;
use Tilta\Payment\Service\ConfigService;

class CreditFacilitiesTest extends TestCase
{
    private Context $context;

    private Customer $customer;

    private Session $customerSession;

    private ConfigService $configService;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->configService = $this->createMock(ConfigService::class);
        $this->customerSession = $this->createMock(Session::class);
        $this->customer = $this->createMock(Customer::class);
        $this->customerSession->method('getCustomer')->willReturn($this->customer);

        $urlBuilder = $this->createMock(Url::class);
        $urlBuilder->method('getUrl')->willReturn('test-123');
        /** @var Context $context */
        $context = $objectManager->getObject(Context::class, [
            'urlBuilder' => $urlBuilder,
        ]);
        $this->context = $context;
    }

    public function testToHtml(): void
    {
        $this->configService->expects($this->once())->method('isConfigReady')->willReturn(true);
        $this->customer->method('getAddresses')->willReturn([
            $this->createMockedAddress([
                'company' => null,
            ]),
            $this->createMockedAddress([
                'company' => 'Tilta GmbH',
            ]),
        ]);

        /** @var CreditFacilities $classToTest */
        $classToTest = (new ObjectManager($this))->getObject(CreditFacilities::class, [
            'context' => $this->context,
            'configService' => $this->configService,
            'customerSession' => $this->customerSession,
            'data' => [
                'path' => 'test-123',
                'label' => 'test 123',
                'current' => true,
            ],
        ]);

        $result = $classToTest->toHtml();
        self::assertNotEmpty($result);
    }

    public function testIfHiddenIfConfigNotReady(): void
    {
        $this->configService->expects($this->once())->method('isConfigReady')->willReturn(false);
        $this->customer->method('getAddresses')->willReturn([
            $this->createMockedAddress([
                'company' => null,
            ]),
            $this->createMockedAddress([
                'company' => 'Tilta GmbH',
            ]),
        ]);

        /** @var CreditFacilities $classToTest */
        $classToTest = (new ObjectManager($this))->getObject(CreditFacilities::class, [
            'context' => $this->context,
            'configService' => $this->configService,
            'customerSession' => $this->customerSession,
            'data' => [
                'path' => 'test-123',
                'label' => 'test 123',
                'current' => true,
            ],
        ]);

        $result = $classToTest->toHtml();
        self::assertEmpty($result, 'block should not return anything because configuration is not ready.');
    }

    public function testIfEmptyIfNoCompanyAddresses(): void
    {
        $this->configService->method('isConfigReady')->willReturn(true);
        $this->customer->method('getAddresses')->willReturn([
            $this->createMockedAddress([
                'company' => null,
            ]),
            $this->createMockedAddress([
                'company' => null,
            ]),
        ]);

        /** @var CreditFacilities $classToTest */
        $classToTest = (new ObjectManager($this))->getObject(CreditFacilities::class, [
            'context' => $this->context,
            'configService' => $this->configService,
            'customerSession' => $this->customerSession,
            'data' => [
                'path' => 'test-123',
                'label' => 'test 123',
                'current' => true,
            ],
        ]);

        $result = $classToTest->toHtml();
        self::assertEmpty($result, 'block should not return anything because not company addresses are given.');
    }

    private function createMockedAddress(array $data): Address
    {
        /** @var Address $address */
        $address = (new ObjectManager($this))->getObject(Address::class, [
            'data' => $data,
        ]);

        return $address;
    }
}
