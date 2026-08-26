<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ClassNotation\ProtectedToPrivateFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\ConstantNotation\NativeConstantInvocationFixer;
use PhpCsFixer\Fixer\ControlStructure\NoSuperfluousElseifFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\CombineConsecutiveIssetsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\CombineConsecutiveUnsetsFixer;
use PhpCsFixer\Fixer\Phpdoc\AlignMultilineCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimConsecutiveBlankLineSeparationFixer;
use PhpCsFixer\Fixer\PhpTag\LinebreakAfterOpeningTagFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestCaseStaticMethodCallsFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestClassRequiresCoversFixer;
use PhpCsFixer\Fixer\ReturnNotation\SimplifiedNullReturnFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/../src',
        __DIR__ . '/../tests',
        __DIR__ . '/../castor.php',
        __DIR__ . '/ecs.php',
        __DIR__ . '/rector.php',
    ])
    ->withSets([
        SetList::PSR_12,
        SetList::CLEAN_CODE,
        SetList::DOCTRINE_ANNOTATIONS,
        SetList::SPACES,
        SetList::ARRAY,
        SetList::COMMON,
        SetList::COMMENTS,
        SetList::CONTROL_STRUCTURES,
        SetList::DOCBLOCK,
        SetList::NAMESPACES,
    ])
    ->withRules([
        StrictParamFixer::class,
        StrictComparisonFixer::class,
        ArrayIndentationFixer::class,
        OrderedImportsFixer::class,
        ProtectedToPrivateFixer::class,
        DeclareStrictTypesFixer::class,
        NativeConstantInvocationFixer::class,
        LinebreakAfterOpeningTagFixer::class,
        CombineConsecutiveIssetsFixer::class,
        CombineConsecutiveUnsetsFixer::class,
        NoSuperfluousElseifFixer::class,
        NoSuperfluousPhpdocTagsFixer::class,
        PhpdocTrimConsecutiveBlankLineSeparationFixer::class,
        PhpdocOrderFixer::class,
        SimplifiedNullReturnFixer::class,
        PhpUnitTestCaseStaticMethodCallsFixer::class,
    ])
    ->withConfiguredRule(ArraySyntaxFixer::class, [
        'syntax' => 'short',
    ])
    ->withConfiguredRule(NativeFunctionInvocationFixer::class, [
        'include' => ['@compiler_optimized'],
        'scope' => 'namespaced',
        'strict' => true,
    ])
    ->withConfiguredRule(HeaderCommentFixer::class, [
        'header' => '',
    ])
    ->withConfiguredRule(AlignMultilineCommentFixer::class, [
        'comment_type' => 'all_multiline',
    ])
    ->withConfiguredRule(PhpUnitTestAnnotationFixer::class, [
        'style' => 'annotation',
    ])
    ->withConfiguredRule(GlobalNamespaceImportFixer::class, [
        'import_classes' => true,
        'import_constants' => true,
        'import_functions' => true,
    ])
    ->withSkip([PhpUnitTestClassRequiresCoversFixer::class])
    ->withParallel();
