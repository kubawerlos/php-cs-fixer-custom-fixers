<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

$config = new Configuration();

return $config
    ->addPathToScan(__DIR__ . '/../src', false)
    ->addPathToExclude(__DIR__ . '/src')
    ->addPathToExclude(__DIR__ . '/../tests');
