<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use PhpCsFixer\Fixer\FixerInterface;

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

require_once __DIR__ . '/../vendor/autoload.php';

chdir(__DIR__ . DIRECTORY_SEPARATOR . '..');

if (!interface_exists(FixerInterface::class)) {
    $phars = [
        __DIR__ . '/../vendor/php-cs-fixer/shim/php-cs-fixer.phar',
        __DIR__ . '/../vendor/php-cs-fixer/shim/php-cs-fixer',
    ];
    foreach ($phars as $phar) {
        if (!file_exists($phar)) {
            continue;
        }
        $pharLoaded = Phar::loadPhar($phar, 'php-cs-fixer.phar');
        if (!$pharLoaded) {
            exit(sprintf('Phar "%s" not loaded!' . PHP_EOL, $phar));
        }
        break;
    }
    require_once 'phar://php-cs-fixer.phar/vendor/autoload.php'; // @phpstan-ignore-line
}
