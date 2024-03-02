<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Concat\JoinStringConcatRector;
use Rector\Config\RectorConfig;
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
        LevelSetList::UP_TO_PHP_72,
        PHPUnitSetList::PHPUNIT_80,
        SymfonySetList::SYMFONY_44,
    ]);

    $rectorConfig->skip([
        __DIR__.'/tests/fixtures/*',
        JoinStringConcatRector::class => [
            __DIR__ . '/src/Automate/Application.php',
        ],
    ]);
};