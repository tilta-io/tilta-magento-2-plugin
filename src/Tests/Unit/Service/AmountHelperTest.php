<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Tilta\Payment\Helper\AmountHelper;

class AmountHelperTest extends TestCase
{
    /**
     * @dataProvider ifAmountGotCorrectlyConvertedToSdkDataProvider
     */
    public function testIfAmountGotCorrectlyConvertedToSdk(float $given, int $expected): void
    {
        self::assertEquals($expected, AmountHelper::toSdk($given));
    }

    /**
     * @dataProvider ifAmountGotCorrectlyConvertedToSdkDataProvider
     */
    public function testIfAmountGotCorrectlyConvertedFromSdk(float $expected, int $given): void
    {
        self::assertEquals($expected, AmountHelper::fromSdk($given));
    }

    public function ifAmountGotCorrectlyConvertedToSdkDataProvider(): array
    {
        return [
            [1, 100],
            [10, 1000],
            [100, 10000],
            [1.0, 100],
            [10.0, 1000],
            [100.0, 10000],
            [1.99, 199],
            [10.99, 1099],
            [100.99, 10099],
        ];
    }
}
