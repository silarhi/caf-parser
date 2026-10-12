<?php

declare(strict_types=1);

/*
 * This file is part of the CAF Parser package.
 *
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;

return RectorConfig::configure()
    ->withImportNames()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets()
    ->withComposerBased(
        phpunit: true
    )
    ->withSkip([
        // str_contains() has no offset: the rule rewrites strpos($haystack, $needle, $offset) into
        // str_contains(substr($haystack, $offset), $needle), copying the rest of the file
        StrContainsRector::class => [__DIR__ . '/src/Parser/PaymentSlipParser.php'],
    ]);
