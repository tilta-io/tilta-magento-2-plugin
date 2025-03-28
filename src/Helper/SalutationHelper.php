<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Helper;

class SalutationHelper
{
    public function getSalutationOptions(): array
    {
        return [
            [
                'value' => 'MR',
                'label' => __('Mr.'),
            ],
            [
                'value' => 'MS',
                'label' => __('Ms.'),
            ],
            [
                'value' => 'OTHER',
                'label' => __('Divers'),
            ],
        ];
    }
}
