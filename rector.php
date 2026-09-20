<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/App',
        __DIR__ . '/config',
        __DIR__ . '/public',
    ])

    // Cible PHP 8.5
    ->withPhpSets()

    // Refactorings de qualité
    ->withCodeQualityLevel(2)

    // Détection de code mort
    ->withDeadCodeLevel(2)

    // Amélioration progressive des types
    ->withTypeCoverageLevel(2);