<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixersDev;

use Infection\Mutator\ProfileList;
use PhpCsFixerCustomFixers\Fixer\NoLeadingSlashInGlobalNamespaceFixer;

final class InfectionConfigBuilder
{
    private const UNWANTED_MUTATORS = [
        'Concat',
        'DecrementInteger',
        'GreaterThan',
        'GreaterThanOrEqualTo',
        'IdenticalEqual',
        'IncrementInteger',
        'IntegerNegation',
        'LessThan',
        'LessThanOrEqualTo',
        'Minus',
        'NotIdentical',
        'NotIdenticalNotEqual',
        'OneZeroInteger',
        'Plus',
        'ReturnRemoval',
        'SyntaxError',
    ];

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $config = [
            '$schema' => './vendor/infection/infection/resources/schema.json',
            'source' => ['directories' => ['../src']],
            'timeout' => 10,
            'threads' => 'max',
            'logs' => [
                'text' => 'php://stdout',
                'github' => true,
                'stryker' => ['report' => 'main'],
            ],
            'phpUnit' => [
                'configDir' => '..',
                'customPath' => '../vendor/phpunit/phpunit/phpunit',
            ],
            'mutators' => [],
            'minMsi' => 100,
        ];

        $mutators = \array_keys(ProfileList::ALL_MUTATORS);
        \sort($mutators);

        foreach ($mutators as $mutator) {
            if (\in_array($mutator, self::UNWANTED_MUTATORS, true)) {
                continue;
            }

            $config['mutators'][$mutator] = true;
        }

        $config['mutators']['ArrayItemRemoval'] = [
            'settings' => ['remove' => 'all'],
            'ignore' => [
                'PhpCsFixerCustomFixers\\Fixer\\*::getConfigurationDefinition',
                'PhpCsFixerCustomFixers\\Fixer\\*::getDefinition',
                'PhpCsFixerCustomFixers\\Fixer\\*::isCandidate',
                NoLeadingSlashInGlobalNamespaceFixer::class . '::isToRemove', // whitespaces and comments cannot be inside FQCN in PHP 8+
            ],
        ];

        $config['mutators']['InstanceOf_'] = [
            'ignoreSourceCodeByRegex' => [
                '\\\\assert\\(.+\\);',
            ],
        ];

        $config['mutators']['LogicalAnd'] = [
            'ignore' => [
                'PhpCsFixerCustomFixers\\Fixer\\*::isCandidate',
            ],
        ];

        $config['mutators']['PublicVisibility'] = [
            'ignore' => [
                'PhpCsFixerCustomFixers\\Fixer\\AbstractFixer::name',
            ],
        ];

        $config['mutators']['TrueValue'] = [
            'ignore' => [
                'PhpCsFixerCustomFixers\\Fixer\\ClassConstantUsageFixer::getClassConstants',
            ],
        ];

        return $config;
    }
}
