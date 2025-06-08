<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

require_once __DIR__ . '/.dev-tools/vendor/autoload.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;
use PhpCsFixerConfig\Rules\LibraryRules;
use PhpCsFixerCustomFixers\Fixer\NoSuperfluousConcatenationFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocNoNamedArgumentsTagFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocOnlyAllowedAnnotationsFixer;
use PhpCsFixerCustomFixers\Fixer\PromotedConstructorPropertyFixer;
use PhpCsFixerCustomFixers\Fixer\TypedClassConstantFixer;
use PhpCsFixerCustomFixers\Fixers;

// sanity check
$expectedPath = realpath(__DIR__ . '/src/Fixers.php');
$actualPath = (new ReflectionClass(Fixers::class))->getFileName();
if ($expectedPath !== $actualPath) {
    printf(
        'Class %s must be loaded from "%s", but loaded from "%s"!' . PHP_EOL,
        Fixers::class,
        $expectedPath,
        $actualPath,
    );
    exit(1);
}

$rules = (new LibraryRules('PHP CS Fixer: custom fixers', 'Kuba Werłos', 2018))->getRules();

$rules[NoSuperfluousConcatenationFixer::name()] = ['allow_preventing_trailing_spaces' => true];

// add new fixers that are not in PhpCsFixerConfig yet
foreach (new Fixers() as $fixer) {
    if ($fixer instanceof DeprecatedFixerInterface) {
        continue;
    }
    if (!array_key_exists($fixer->getName(), $rules)) {
        $rules[$fixer->getName()] = true;
    }
}

unset($rules['assign_null_coalescing_to_coalesce_equal']); // TODO: remove when dropping support to PHP <8.0
unset($rules['get_class_to_class_keyword']); // TODO: remove when dropping support to PHP <8.0
unset($rules['modernize_strpos']); // TODO: remove when dropping support to PHP <8.0
unset($rules['php_unit_attributes']); // TODO: remove when dropping support to PHP <8.0
unset($rules[PromotedConstructorPropertyFixer::name()]); // TODO: remove when dropping support to PHP <8.0
unset($rules[TypedClassConstantFixer::name()]); // TODO: remove when dropping support to PHP <8.3
$rules['trailing_comma_in_multiline'] = ['after_heredoc' => true, 'elements' => ['arguments', 'arrays']]; // TODO: remove when dropping support to PHP <8.0
$rules[PhpdocNoNamedArgumentsTagFixer::name()] = false; // TODO: change to ['directory' => __DIR__ . '/src/']

$rules[PhpdocOnlyAllowedAnnotationsFixer::name()]['elements'][] = 'phpstan-type';
$rules[PhpdocOnlyAllowedAnnotationsFixer::name()]['elements'][] = 'codeCoverageIgnoreStart';
$rules[PhpdocOnlyAllowedAnnotationsFixer::name()]['elements'][] = 'codeCoverageIgnoreEnd';

foreach (new PhpCsFixerCustomFixersDev\Fixers() as $fixer) {
    $rules[$fixer->getName()] = true;
}

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->registerCustomFixers(new Fixers())
    ->registerCustomFixers(new PhpCsFixerCustomFixersDev\Fixers())
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
    ->setFinder(
        Finder::create()
            ->ignoreDotFiles(false)
            ->in(__DIR__),
    )
    ->setRules($rules);
