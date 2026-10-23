<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
    )
    ->withComposerBased(phpunit: true, symfony: true)
    ->withSkip([
        __DIR__.'/tests/fixtures/*',
    ]);
