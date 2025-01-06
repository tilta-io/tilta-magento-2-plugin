<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

if (!class_exists(ComponentRegistrar::class)) {
    return; // static code-analyse
}

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Tilta_Payment', __DIR__);
