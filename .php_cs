<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) Kuba Werłos <werlos@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

$header = \trim('
This file is part of PHP CS Fixer: custom fixers.

(c) Kuba Werłos <werlos@gmail.com>

For the full copyright and license information, please view
the LICENSE file that was distributed with this source code.
');

require_once __DIR__ . '/dev-tools/vendor/kubawerlos/php-cs-fixer-config/src/Rules/RulesInterface.php';
require_once __DIR__ . '/dev-tools/vendor/kubawerlos/php-cs-fixer-config/src/Rules/LibraryRules.php';
$rules = (new PhpCsFixerConfig\Rules\LibraryRules('PHP CS Fixer: custom fixers'))->getRules();

// PhpCsFixerCustomFixersDev\Fixer\OrderedClassElementsInternalFixer will handle this
unset($rules['ordered_class_elements']);
unset($rules['ordered_interfaces']);

foreach (new PhpCsFixerCustomFixersDev\Fixers() as $fixer) {
    $rules[$fixer->getName()] = true;
}

return PhpCsFixer\Config::create()
    ->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers())
    ->registerCustomFixers(new PhpCsFixerCustomFixersDev\Fixers())
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->files()
            ->in(__DIR__ . '/dev-tools/src')
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/tests')
            ->notName('php-cs-fixer.config.*.php')
            ->append([
                __DIR__ . '/dev-tools/readme',
                __FILE__,
            ])
    )
    ->setRules($rules);
