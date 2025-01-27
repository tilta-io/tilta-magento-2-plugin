<?php declare(strict_types=1);

use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\AssignmentInConditionSniff;
use PhpCsFixer\Fixer\Basic\PsrAutoloadingFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\ClassNotation\ProtectedToPrivateFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSpaceFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoEmptyReturnFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->ruleWithConfiguration(HeaderCommentFixer::class, ['header' => '(c) WEBiDEA

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.', 'separate' => 'bottom', 'location' => 'after_open', 'comment_type' => 'comment']);

    $ecsConfig->sets([
        SetList::CLEAN_CODE,
        SetList::COMMON,
        SetList::STRICT,
        SetList::PSR_12,
    ]);

    $ecsConfig->rules([
        PsrAutoloadingFixer::class
    ]);

    $ecsConfig->skip([
        ProtectedToPrivateFixer::class,
        NotOperatorWithSpaceFixer::class,
        NotOperatorWithSuccessorSpaceFixer::class,
        AssignmentInConditionSniff::class,
        OrderedClassElementsFixer::class,
        NoSuperfluousPhpdocTagsFixer::class => [
            __DIR__ . '/src/Api',
        ],
        PhpdocNoEmptyReturnFixer::class => [
            __DIR__ . '/src/Api',
        ],
        ArrayListItemNewlineFixer::class => [
            __DIR__ . '/src/Tests',
        ],
        ArrayOpenerAndCloserNewlineFixer::class => [
            __DIR__ . '/src/Tests',
        ],
    ]);

    $ecsConfig->paths([
        __DIR__ . '/src',
    ]);
};
