<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Unit\Block\Checkout;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Block\Widget\Telephone;
use Magento\Customer\Model\Data\Address;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\Manager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tilta\Payment\Controller\Facility\RequestPost;
use Tilta\Payment\Model\CustomerAddressBuyer;
use Tilta\Payment\Service\BuyerService;
use Tilta\Payment\Service\FacilityService;
use Tilta\Payment\Tests\Mock\HttpRequest;
use Tilta\Payment\Tests\Mock\Redirect;
use Tilta\Payment\Tests\Mock\Url;

class RequestPostTest extends TestCase
{
    private Manager $messageManager;

    private RequestInterface $request;

    private BuyerService $buyerService;

    private FacilityService $facilityService;

    private RedirectFactory $redirectFactory;

    private AddressRepositoryInterface $addressRepository;

    private Session $customerSession;

    protected function setUp(): void
    {
        $this->request = new HttpRequest();
        $this->messageManager = $this->createMock(Manager::class);
        $this->buyerService = $this->createMock(BuyerService::class);
        $this->facilityService = $this->createMock(FacilityService::class);
        $this->addressRepository = $this->createMock(AddressRepositoryInterface::class);
        $this->customerSession = $this->createMock(Session::class);

        $this->redirectFactory = $this->createMock(RedirectFactory::class);
        $this->redirectFactory->method('create')->willReturn(new Redirect($this->createMock(\Magento\Store\App\Response\Redirect::class), new Url()));
    }

    public function textExecute(): void
    {
        /** @var Address $address */
        $address = (new ObjectManager($this))->getObject(Address::class);
        $address->setCustomerId(1);
        $this->addressRepository->method('getById')->willReturn($address);
        $this->customerSession->method('getCustomerId')->willReturn(1);

        $controller = new RequestPost($this->addressRepository, $this->customerSession, $this->request, $this->messageManager, $this->buyerService, $this->facilityService, $this->redirectFactory);

        $this->messageManager->expects($this->never())->method('addErrorMessage');
        $this->messageManager->expects($this->once())->method('addSuccessMessage');
        $this->buyerService->expects($this->once())->method('updateCustomerAddressData');
        $this->facilityService->expects($this->once())->method('createFacilityForBuyerIfNotExist');

        $requestData = $this->getValidRequestData();
        $this->request->setParams($requestData);
        $result = $controller->execute();
        self::assertInstanceOf(Redirect::class, $result);
        self::assertEquals('*/*/list', $result->getUrl());
    }

    /**
     * @dataProvider validationDataProvider
     */
    public function testValidation(string $field, mixed $value, string $expectedErrorMessage): void
    {
        /** @var Address $address */
        $address = (new ObjectManager($this))->getObject(Address::class);
        $address->setCustomerId(1);
        $this->addressRepository->method('getById')->willReturn($address);
        $this->customerSession->method('getCustomerId')->willReturn(1);

        $controller = new RequestPost($this->addressRepository, $this->customerSession, $this->request, $this->messageManager, $this->buyerService, $this->facilityService, $this->redirectFactory);

        $this->messageManager->expects($this->exactly(1))->method('addErrorMessage')->with($expectedErrorMessage);
        $requestData = $this->getValidRequestData();
        $requestData[$field] = $value;
        $this->request->setParams($requestData);
        $result = $controller->execute();
        self::assertInstanceOf(Redirect::class, $result);
        self::assertEquals('*/*/request', $result->getUrl());
    }

    public static function validationDataProvider(): array
    {
        return [
            [Telephone::ATTRIBUTE_CODE, null, 'Please provide your phone number.'],
            [Telephone::ATTRIBUTE_CODE, '', 'Please provide your phone number.'],
            [CustomerAddressBuyer::LEGAL_FORM, null, 'Please provide the legal form.'],
            [CustomerAddressBuyer::LEGAL_FORM, '', 'Please provide the legal form.'],
            [CustomerAddressBuyer::INCORPORATED_AT, null, 'Please provide the date of incorporation.'],
            [CustomerAddressBuyer::INCORPORATED_AT, '', 'Please provide the date of incorporation.'],
            [CustomerAddressBuyer::INCORPORATED_AT, [], 'Please provide the date of incorporation.'],
            [CustomerAddressBuyer::INCORPORATED_AT, ['abc', 'def', 'ghi'], 'Please provide the date of incorporation.'],
            [CustomerAddressBuyer::INCORPORATED_AT, '2024-0-0', 'Please provide the date of incorporation.'],
        ];
    }

    /**
     * @dataProvider incorporatedAtDataProvider
     */
    public function testIncorporatedAt(mixed $value): void
    {
        /** @var Address $address */
        $address = (new ObjectManager($this))->getObject(Address::class);
        $address->setCustomerId(1);
        $this->addressRepository->method('getById')->willReturn($address);
        $this->customerSession->method('getCustomerId')->willReturn(1);

        $controller = new RequestPost($this->addressRepository, $this->customerSession, $this->request, $this->messageManager, $this->buyerService, $this->facilityService, $this->redirectFactory);

        $this->messageManager->expects($this->never())->method('addErrorMessage');
        $this->messageManager->expects($this->once())->method('addSuccessMessage');
        $this->buyerService->expects($this->once())->method('updateCustomerAddressData');
        $this->facilityService->expects($this->once())->method('createFacilityForBuyerIfNotExist');

        $requestData = $this->getValidRequestData();
        $requestData[CustomerAddressBuyer::INCORPORATED_AT] = $value;
        $this->request->setParams($requestData);
        $result = $controller->execute();
        self::assertInstanceOf(Redirect::class, $result);
        self::assertEquals('*/*/list', $result->getUrl());
    }

    public static function incorporatedAtDataProvider(): array
    {
        return [
            ['2024-01-31'],
            [['year' => 2024, 'month' => 1, 'day' => 31]],
        ];
    }

    private function getValidRequestData(): array
    {
        return [
            Telephone::ATTRIBUTE_CODE => '+49123456879',
            CustomerAddressBuyer::LEGAL_FORM => 'PUBLIC_COMPANY',
            CustomerAddressBuyer::INCORPORATED_AT => '2024-01-05',
        ];
    }
}
