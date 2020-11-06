<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpCsFixerCustomFixersDev\Readme;

use PhpCsFixer\Console\Command\HelpCommand;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\StdinFileInfo;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Fixer\AbstractFixer;
use PhpCsFixerCustomFixers\Fixer\DeprecatingFixerInterface;
use PhpCsFixerCustomFixers\Fixers;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
final class ReadmeCommand extends BaseCommand
{
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
            . $this->contributing() . "\n"
        );

        return 0;
    }

    private function badges(): string
    {
        return \implode("\n", [
            $this->badge(
                'Latest stable version',
                \sprintf('%s/packagist/v/%s.svg?label=current%%20version', self::SHIELDS_HOST, $this->composer()->name),
                \sprintf('https://packagist.org/packages/%s', $this->composer()->name)
            ),
            $this->badge(
                'PHP version',
                \sprintf('%s/packagist/php-v/%s.svg', self::SHIELDS_HOST, $this->composer()->name),
                'https://php.net'
            ),
            $this->badge(
                'License',
                \sprintf('%s/github/license/%s.svg', self::SHIELDS_HOST, $this->composer()->name),
                'LICENSE'
            ),
            $this->badge(
                'Repository size',
                \sprintf('https://github-size-badge.herokuapp.com/%s.svg', $this->composer()->name)
            ),
            $this->badge(
                'Last commit',
                \sprintf('%s/github/last-commit/%s.svg', self::SHIELDS_HOST, $this->composer()->name),
                \sprintf('https://github.com/%s/commits', $this->composer()->name)
            ),
            '',
            $this->badge(
                'CI Status',
                \sprintf('https://github.com/%s/workflows/CI/badge.svg?branch=master&event=push', $this->composer()->name),
                \sprintf('https://github.com/%s/actions', $this->composer()->name)
            ),
            $this->badge(
                'Code coverage',
                \sprintf('%s/coveralls/github/%s/master.svg', self::SHIELDS_HOST, $this->composer()->name),
                \sprintf('https://coveralls.io/github/%s?branch=master', $this->composer()->name)
            ),
            $this->badge(
                'Tests',
                \sprintf('%s/badge/tests-%d-brightgreen.svg', self::SHIELDS_HOST, $this->numberOfTests())
            ),
            $this->badge(
                'Mutation testing badge',
                \sprintf('https://badge.stryker-mutator.io/github.com/%s/master', $this->composer()->name),
                'https://stryker-mutator.github.io'
            ),
            $this->badge(
                'Psalm type coverage',
                \sprintf('https://shepherd.dev/github/%s/coverage.svg', $this->composer()->name),
                \sprintf('https://shepherd.dev/github/%s', $this->composer()->name)
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

        return \substr_count($process->getOutput(), PHP_EOL) - 3; // 3 is for header
    }

    private function description(): string
    {
        return \str_replace(
            'PHP CS Fixer',
            '[PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer)',
            $this->composer()->description
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
            $this->composer()->name
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
',
            $this->diff(
                \file_get_contents(__DIR__ . '/php-cs-fixer.config.before.php'),
                \file_get_contents(__DIR__ . '/php-cs-fixer.config.after.php')
            )
        );
    }

    private function fixers(): string
    {
        $output = '## Fixers';

        /** @var AbstractFixer $fixer */
        foreach (new Fixers() as $fixer) {
            $reflection = new \ReflectionClass($fixer);

            $output .= \sprintf(
                "\n#### %s\n%s",
                $reflection->getShortName(),
                $fixer->getDefinition()->getSummary()
            );

            $output .= !$fixer instanceof DeprecatingFixerInterface ? '' : \sprintf(
                "\n  *To be deprecated after [this](https://github.com/FriendsOfPHP/PHP-CS-Fixer/pull/%d) is merged and released.*",
                $fixer->getPullRequestId()
            );

            $output .= $fixer instanceof DeprecatedFixerInterface ? \sprintf("\n  DEPRECATED: use `%s` instead.", \implode('`, `', $fixer->getSuccessorsNames())) : '';

            if ($fixer->isRisky()) {
                $output .= \sprintf(
                    "\n  *Risky: %s.*",
                    $fixer->getDefinition()->getRiskyDescription()
                );
            }

            if ($fixer instanceof ConfigurationDefinitionFixerInterface) {
                $output .= "\nConfiguration options:";

                foreach ($fixer->getConfigurationDefinition()->getOptions() as $option) {
                    if ($option->getAllowedValues() !== null) {
                        $allowed = \array_map(static function (string $value): string {
                            return \sprintf('\'%s\'', $value);
                        }, $option->getAllowedValues());
                    } else {
                        /** @var string[] $allowed */
                        $allowed = $option->getAllowedTypes();
                    }
                    $output .= \sprintf(
                        "\n- `%s` (`%s`): %s; defaults to `%s`",
                        $option->getName(),
                        \implode('`, `', $allowed),
                        $option->getDescription(),
                        HelpCommand::toString($option->getDefault())
                    );
                }
            }

            /** @var CodeSample $codeSample */
            $codeSample = $fixer->getDefinition()->getCodeSamples()[0];

            $originalCode = $codeSample->getCode();
            if ($fixer instanceof ConfigurableFixerInterface) {
                $fixer->configure($codeSample->getConfiguration());
            }
            $tokens = Tokens::fromCode($originalCode);
            $fixer->fix(new StdinFileInfo(), $tokens);
            $fixedCode = $tokens->generateCode();

            $output .= \sprintf(
                "\n```diff\n%s\n```\n",
                $this->diff($originalCode, $fixedCode)
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

        /** @var int $start */
        $start = \strpos($diff, "\n", 10);

        return \substr($diff, $start + 1, -1);
    }

    private function contributing(): string
    {
        return \sprintf(
            '## Contributing
Request feature or report bug by creating [issue](https://github.com/%s/issues).

Alternatively, fork the repo, develop your changes, regenerate `README.md`:
```bash
%s
```
make sure all checks pass:
```bash
./dev-tools/check_file_permissions.sh
./dev-tools/check_trailing_whitespaces.sh
composer verify
composer infection
```
and submit pull request.',
            $this->composer()->name,
            \end($this->composer()->scripts->fix)
        );
    }

    private function composer(): \stdClass
    {
        return \json_decode(\file_get_contents(__DIR__ . '/../../../composer.json'));
    }
}
