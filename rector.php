<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Concat\JoinStringConcatRector;
use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::TYPE_DECLARATION,
        LevelSetList::UP_TO_PHP_83,
        PHPUnitSetList::PHPUNIT_100,
        SymfonySetList::SYMFONY_64,
    ]);

    $rectorConfig->skip([
        __DIR__.'/tests/fixtures/*',
        AddOverrideAttributeToOverriddenMethodsRector::class,
        JoinStringConcatRector::class => [
            __DIR__ . '/src/Automate/Application.php',
        ],
    ]);
};