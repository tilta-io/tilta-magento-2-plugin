<?php

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

if (!class_exists(ComponentRegistrar::class)) {
    return; // static code-analyse
}

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Tilta_Payment', __DIR__);
