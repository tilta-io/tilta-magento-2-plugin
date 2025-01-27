<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictStringParamConcatRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);
    $rectorConfig->autoloadPaths([__DIR__ . '/vendor/autoload.php']);

    $rectorConfig->skip([
        DisallowedEmptyRuleFixerRector::class,
        MixedTypeRector::class => [
            __DIR__ . '/src/Api/',
        ],
        StrictStringParamConcatRector::class => [
            __DIR__ . '/src/Tests/Mock/Url.php'
        ]
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    $rectorConfig->removeUnusedImports();
    $rectorConfig->importNames(true, false);

    // define sets of rules
    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::CODING_STYLE,
        LevelSetList::UP_TO_PHP_81,
    ]);
};
