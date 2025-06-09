<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

echo getcwd() . PHP_EOL;
chdir(dirname(__DIR__));
echo getcwd() . PHP_EOL;
chdir(__DIR__ . '/..');
echo getcwd() . PHP_EOL;
