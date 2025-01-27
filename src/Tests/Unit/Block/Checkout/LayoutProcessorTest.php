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
use Tilta\Payment\Block\Checkout\LayoutProcessor;
use Tilta\Payment\Service\LegalFormService;
use Tilta\Sdk\Exception\GatewayException\UnexpectedServerResponse;

class LayoutProcessorTest extends TestCase
{
    public function testToHtml(): void
    {
        $processor = new LayoutProcessor(
            new ArrayManager(),
            $legalFormService = $this->createMock(LegalFormService::class),
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

        $result = $processor->process([
            'test1' => [
                'test2' => [
                    'test3' => [
                        'tilta-request-facility-form-fieldset' => [
                            'test4' => 'test4_value',
                            'children' => [
                                'test5' => 'test5_value',
                                'legal_form' => [
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

        self::assertArrayHasKey('legal_form', $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']['children']);
        self::assertArrayHasKey('options', $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']['children']['legal_form']);
        self::assertEquals([
            [
                'value' => 'value1',
                'label' => 'label1',
            ],
            [
                'value' => 'value2',
                'label' => 'label2',
            ],
        ], $result['test1']['test2']['test3']['tilta-request-facility-form-fieldset']['children']['legal_form']['options']);
    }

    public function testIfApiExceptionGotHandled(): void
    {
        $processor = new LayoutProcessor(
            new ArrayManager(),
            $legalFormService = $this->createMock(LegalFormService::class),
            $this->createMock(LoggerInterface::class),
        );
        $legalFormService->expects($this->once())->method('getLegalForms')->willThrowException(new UnexpectedServerResponse(123));

        $result = $processor->process([
            'test1' => 'value1',
            'tilta-request-facility-form-fieldset' => [
                'test2' => 'value2',
            ],
        ]);

        self::assertIsArray($result);
        self::assertArrayHasKey('test1', $result);
        self::assertEquals('value1', $result['test1']);
        self::assertArrayHasKey('test1', $result);
        self::assertEquals([
            'test2' => 'value2',
        ], $result['tilta-request-facility-form-fieldset']);
    }

    public function testIfInputIsOutputIfFieldsetIsNotGiven(): void
    {
        $processor = new LayoutProcessor(
            new ArrayManager(),
            $legalFormService = $this->createMock(LegalFormService::class),
            $this->createMock(LoggerInterface::class),
        );
        $legalFormService->expects($this->never())->method('getLegalForms');

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
