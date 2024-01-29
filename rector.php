<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\Set\PHPUnitLevelSetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $config): void {
    $config->import(SetList::DEAD_CODE);
    $config->import(LevelSetList::UP_TO_PHP_80);
    $config->import(SymfonySetList::SYMFONY_CODE_QUALITY);
    $config->import(PHPUnitLevelSetList::UP_TO_PHPUNIT_100);
    $config->import(PHPUnitSetList::PHPUNIT_CODE_QUALITY);
    $config->parallel();
    $config->paths([__DIR__ . '/src', __DIR__ . '/tests']);
    $config->skip(
        [
            __DIR__ . '/src/IndefiniteLengthMapObject.php',
            __DIR__ . '/src/MapObject.php',
            PreferPHPUnitThisCallRector::class,
        ]
    );
    $config->phpVersion(PhpVersion::PHP_80);
    $config->importNames();
    $config->importShortClasses();
};
