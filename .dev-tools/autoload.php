<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

if (!interface_exists('PhpCsFixer\\Fixer\\FixerInterface')) {
    $phars = [
        __DIR__ . '/../vendor/php-cs-fixer/shim/php-cs-fixer',
        __DIR__ . '/vendor/php-cs-fixer/shim/php-cs-fixer',
    ];

    foreach ($phars as $phar) {
        if (file_exists($phar)) {
            $pharLoaded = Phar::loadPhar($phar, 'php-cs-fixer.phar');
            if (!$pharLoaded) {
                exit(sprintf('Phar "%s" not loaded!' . PHP_EOL, $phar));
            }

            require_once 'phar://php-cs-fixer.phar/vendor/autoload.php';
            break;
        }
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/vendor/autoload.php';
