<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/vendor/autoload.php';

$configBuilder = new PhpCsFixerCustomFixersDev\InfectionConfigBuilder();
$config = $configBuilder->build();

file_put_contents(__DIR__ . '/infection.json', json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n");
