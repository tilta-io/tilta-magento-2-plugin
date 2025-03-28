<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Unit\Block\Checkout;

use Magento\Framework\Stdlib\ArrayManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;
use Tilta\Payment\Block\Checkout\LayoutProcessor;
use Tilta\Payment\Helper\SalutationHelper;
use Tilta\Payment\Service\LegalFormService;
use Tilta\Sdk\Exception\GatewayException\UnexpectedServerResponse;

class LayoutProcessorTest extends TestCase
{
    public function testToHtml(): void
    {
        $processor = new LayoutProcessor(
            new ArrayManager(),
            $legalFormService = $this->createMock(LegalFormService::class),
            $salutationHelper = $this->createMock(SalutationHelper::class),
            $this->createMock(LoggerInterface::class),
        );
        $legalFormService->method('getLegalForms')->willReturn([
            [
                'value' => 'value1',
                'label' => 'label1',
            ],
            [
                'value' => 'value2',
                'label' => 'label2',
            ],
        ]);

        $salutationHelper->method('getSalutationOptions')->willReturn([
            [
                'value' => 'value4',
                'label' => 'label4',
            ],
            [
                'value' => 'value5',
                'label' => 'label5',
            ],
        ]);

        $result = $processor->process([
            'test1' => [
                'test2' => [
                    'test3' => [
                        'tilta-request-facility-form-fieldset' => [
                            'test4' => 'test4_value',
                            'children' => [
                                'test5' => 'test5_value',
                                CustomerAddressBuyerInterface::LEGAL_FORM => [
                                    'test6' => 'test6_value',
                                ],
                                CustomerAddressBuyerInterface::SOLE_TRADER_SALUTATION => [
                                    'test6' => 'test6_value',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertIsArray($result);
        self::assertArrayHasKey('test1', $result);
        self::assertArrayHasKey('test2', $result['test1']);
        self::assertArrayHasKey('test3', $result['test1']['test2']);
        self::assertArrayHasKey('tilta-request-facility-form-fieldset', $result['test1']['test2']['test3']);
        self::assertArrayHasKey('test4', $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']);
        self::assertEquals('test4_value', $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']['test4']);
        self::assertArrayHasKey('children', $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']);
        self::assertArrayHasKey('test5', $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']['children']);
        self::assertEquals('test5_value', $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']['children']['test5']);

        // check legal-forms
        self::assertArrayHasKey(CustomerAddressBuyerInterface::LEGAL_FORM, $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']['children']);
        self::assertArrayHasKey('options', $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']['children'][CustomerAddressBuyerInterface::LEGAL_FORM]);
        self::assertEquals([
            [
                'value' => 'value1',
                'label' => 'label1',
            ],
            [
                'value' => 'value2',
                'label' => 'label2',
            ],
        ], $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']['children'][CustomerAddressBuyerInterface::LEGAL_FORM]['options']);

        // check salutations
        self::assertArrayHasKey(CustomerAddressBuyerInterface::SOLE_TRADER_SALUTATION, $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']['children']);
        self::assertArrayHasKey('options', $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']['children'][CustomerAddressBuyerInterface::SOLE_TRADER_SALUTATION]);
        self::assertEquals([
            [
                'value' => 'value4',
                'label' => 'label4',
            ],
            [
                'value' => 'value5',
                'label' => 'label5',
            ],
        ], $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']['children'][CustomerAddressBuyerInterface::SOLE_TRADER_SALUTATION]['options']);
    }

    public function testIfApiExceptionGotHandled(): void
    {
        $processor = new LayoutProcessor(
            new ArrayManager(),
            $legalFormService = $this->createMock(LegalFormService::class),
            $this->createMock(SalutationHelper::class),
            $this->createMock(LoggerInterface::class),
        );
        $legalFormService->expects($this->once())->method('getLegalForms')->willThrowException(new UnexpectedServerResponse(123));

        $result = $processor->process([
            'test1' => 'value1',
            'tilta-request-facility-form-fieldset' => [
                'test2' => 'value2',
                'children' => [
                    'test5' => 'test5_value',
                    CustomerAddressBuyerInterface::LEGAL_FORM => [
                        'test6' => 'test6_value',
                    ],
                ],
            ],
        ]);

        self::assertIsArray($result);
        self::assertArrayHasKey('test1', $result);
        self::assertEquals('value1', $result['test1']);
        self::assertArrayHasKey('test1', $result);
        self::assertEquals([
            'test2' => 'value2',
            'children' => [
                'test5' => 'test5_value',
                CustomerAddressBuyerInterface::LEGAL_FORM => [
                    'test6' => 'test6_value',
                ],
            ],
        ], $result['tilta-request-facility-form-fieldset']);
    }

    public function testIfInputIsOutputIfFieldsetIsNotGiven(): void
    {
        $processor = new LayoutProcessor(
            new ArrayManager(),
            $legalFormService = $this->createMock(LegalFormService::class),
            $salutationHelper = $this->createMock(SalutationHelper::class),
            $this->createMock(LoggerInterface::class),
        );
        $legalFormService->expects($this->never())->method('getLegalForms');
        $salutationHelper->expects($this->never())->method('getSalutationOptions');

        $input = [
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => [
                'test31' => 'value31',
                'test32' => 'value32',
            ],
        ];
        $result = $processor->process($input);

        self::assertEquals($input, $result);
    }
}
