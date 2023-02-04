<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixersDev\Readme;

use PhpCsFixer\Console\Command\HelpCommand;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSampleInterface;
use PhpCsFixer\StdinFileInfo;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PhpCsFixerCustomFixers\Fixer\AbstractFixer;
use PhpCsFixerCustomFixers\Fixer\DataProviderStaticFixer;
use PhpCsFixerCustomFixers\Fixers;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
final class ReadmeCommand extends Command
{
    protected static $defaultName = 'readme';

    private const NAME = 'PHP CS Fixer: custom fixers';
    private const SHIELDS_HOST = 'https://img.shields.io';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->write(
            \sprintf('# %s', self::NAME) . "\n\n"
            . $this->badges() . "\n\n"
            . $this->description() . "\n\n"
            . $this->installation() . "\n\n"
            . $this->usage() . "\n\n"
            . $this->fixers() . "\n\n"
            . $this->contributing() . "\n",
        );

        return self::SUCCESS;
    }

    private function badges(): string
    {
        return \implode("\n", [
            $this->badge(
                'Latest stable version',
                \sprintf('%s/packagist/v/%s.svg?label=current%%20version', self::SHIELDS_HOST, $this->composer()->name),
                \sprintf('https://packagist.org/packages/%s', $this->composer()->name),
            ),
            $this->badge(
                'PHP version',
                \sprintf('%s/packagist/php-v/%s.svg', self::SHIELDS_HOST, $this->composer()->name),
                'https://php.net',
            ),
            $this->badge(
                'License',
                \sprintf('%s/github/license/%s.svg', self::SHIELDS_HOST, $this->composer()->name),
                'LICENSE',
            ),
            $this->badge(
                'Tests',
                \sprintf('%s/badge/tests-%d-brightgreen.svg', self::SHIELDS_HOST, $this->numberOfTests()),
            ),
            $this->badge(
                'Downloads',
                \sprintf('%s/packagist/dt/%s.svg', self::SHIELDS_HOST, $this->composer()->name),
                \sprintf('https://packagist.org/packages/%s', $this->composer()->name),
            ),
            '',
            $this->badge(
                'CI Status',
                \sprintf('https://github.com/%s/workflows/CI/badge.svg?branch=main', $this->composer()->name),
                \sprintf('https://github.com/%s/actions', $this->composer()->name),
            ),
            $this->badge(
                'Code coverage',
                \sprintf('%s/coveralls/github/%s/main.svg', self::SHIELDS_HOST, $this->composer()->name),
                \sprintf('https://coveralls.io/github/%s?branch=main', $this->composer()->name),
            ),
            $this->badge(
                'Mutation testing badge',
                \sprintf('https://badge.stryker-mutator.io/github.com/%s/main', $this->composer()->name),
                \sprintf('https://dashboard.stryker-mutator.io/reports/github.com/%s/main', $this->composer()->name),
            ),
            $this->badge(
                'Psalm type coverage',
                \sprintf('https://shepherd.dev/github/%s/coverage.svg', $this->composer()->name),
                \sprintf('https://shepherd.dev/github/%s', $this->composer()->name),
            ),
        ]);
    }

    private function badge(string $description, string $imageUrl, ?string $targetUrl = null): string
    {
        $badge = \sprintf('![%s](%s)', $description, $imageUrl);

        if ($targetUrl !== null) {
            $badge = \sprintf('[%s](%s)', $badge, $targetUrl);
        }

        return $badge;
    }

    private function numberOfTests(): int
    {
        $process = new Process([__DIR__ . '/../../../vendor/bin/phpunit', '--list-tests'], __DIR__ . '/../../..');
        $process->run();

        return \substr_count($process->getOutput(), \PHP_EOL) - 3; // 3 is for header
    }

    private function description(): string
    {
        return \str_replace(
            'PHP CS Fixer',
            '[PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer)',
            $this->composer()->description,
        ) . '.';
    }

    private function installation(): string
    {
        return \sprintf(
            '## Installation
%s can be installed by running:
```bash
composer require --dev %s
```
',
            self::NAME,
            $this->composer()->name,
        );
    }

    private function usage(): string
    {
        return \sprintf(
            '## Usage
In your PHP CS Fixer configuration register fixers and use them:
```diff
%s
```
:warning: When PHP CS Fixer is installed via [`php-cs-fixer/shim`](https://github.com/PHP-CS-Fixer/shim) package,
requiring autoload is needed to load `PhpCsFixerCustomFixers` classes:
```php
require_once __DIR__ . \'/vendor/autoload.php\';
```
',
            $this->diff(
                \file_get_contents(__DIR__ . '/php-cs-fixer.config.before.txt'),
                \file_get_contents(__DIR__ . '/php-cs-fixer.config.after.txt'),
            ),
        );
    }

    private function fixers(): string
    {
        $output = '## Fixers';

        /** @var AbstractFixer $fixer */
        foreach (new Fixers() as $fixer) {
            if ($fixer instanceof WhitespacesAwareFixerInterface) {
                $fixer->setWhitespacesConfig(new WhitespacesFixerConfig());
            }

            $reflectionClass = new \ReflectionClass($fixer);

            $output .= \sprintf(
                "\n#### %s\n%s",
                $reflectionClass->getShortName(),
                $fixer->getDefinition()->getSummary(),
            );

            $output .= $fixer instanceof DeprecatedFixerInterface ? \sprintf("\n  DEPRECATED: use `%s` instead.", \implode('`, `', $fixer->getSuccessorsNames())) : '';

            if ($fixer instanceof DataProviderStaticFixer) {
                $fixer->configure(['force' => true]);
            }
            if ($fixer->isRisky()) {
                $output .= \sprintf(
                    "\n  *Risky: %s.*",
                    $fixer->getDefinition()->getRiskyDescription(),
                );
            }
            if ($fixer instanceof DataProviderStaticFixer) {
                $fixer->configure(['force' => false]);
            }

            if ($fixer instanceof ConfigurableFixerInterface) {
                $output .= "\nConfiguration options:";

                foreach ($fixer->getConfigurationDefinition()->getOptions() as $option) {
                    if ($option->getAllowedValues() !== null) {
                        $allowed = \array_map(static fn (string $value): string => \sprintf('\'%s\'', $value), $option->getAllowedValues());
                    } else {
                        /** @var array<string> $allowed */
                        $allowed = $option->getAllowedTypes();
                    }
                    $output .= \sprintf(
                        "\n- `%s` (`%s`): %s; defaults to `%s`",
                        $option->getName(),
                        \implode('`, `', $allowed),
                        $option->getDescription(),
                        HelpCommand::toString($option->getDefault()),
                    );
                }
            }

            $codeSample = $fixer->getDefinition()->getCodeSamples()[0];
            \assert($codeSample instanceof CodeSampleInterface);

            $originalCode = $codeSample->getCode();
            if ($fixer instanceof ConfigurableFixerInterface) {
                $fixer->configure($codeSample->getConfiguration() ?? []);
            }
            $tokens = Tokens::fromCode($originalCode);
            $fixer->fix(new StdinFileInfo(), $tokens);
            $fixedCode = $tokens->generateCode();

            $output .= \sprintf(
                "\n```diff\n%s\n```\n",
                $this->diff($originalCode, $fixedCode),
            );
        }

        return $output;
    }

    private function diff(string $from, string $to): string
    {
        static $differ;

        if ($differ === null) {
            $differ = new Differ(new StrictUnifiedDiffOutputBuilder([
                'contextLines' => 1024,
                'fromFile' => '',
                'toFile' => '',
            ]));
        }

        $diff = $differ->diff($from, $to);

        $start = \strpos($diff, "\n", 10);
        \assert(\is_int($start));

        return \substr($diff, $start + 1, -1);
    }

    private function contributing(): string
    {
        return \sprintf(
            '## Contributing
Request feature or report bug by creating [issue](https://github.com/%s/issues).

Alternatively, fork the repository, develop your changes, make sure everything is fine:
```bash
composer verify
```
and submit pull request.',
            $this->composer()->name,
        );
    }

    private function composer(): \stdClass
    {
        return \json_decode(\file_get_contents(__DIR__ . '/../../../composer.json'));
    }
}
