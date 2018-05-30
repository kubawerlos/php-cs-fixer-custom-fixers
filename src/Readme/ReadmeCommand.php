<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Readme;

use PhpCsFixer\StdinFileInfo;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Fixers;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ReadmeCommand extends BaseCommand
{
    private const SHIELDS_HOST  = 'https://img.shields.io';
    private const CODECOV_URL   = 'https://codecov.io/gh/kubawerlos/php-cs-fixer-custom-fixers';
    private const PACKAGIST_URL = 'https://packagist.org/packages/kubawerlos/php-cs-fixer-custom-fixers';
    private const TRAVIS_URL    = 'https://travis-ci.org/kubawerlos/php-cs-fixer-custom-fixers';

    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        $output->writeln('# PHP CS Fixer: custom fixers');
        $output->writeln($this->badges());
        $output->writeln($this->description());
        $output->writeln($this->installation());
        $output->writeln($this->usage());
        $output->writeln($this->fixers());
        $output->writeln($this->contributing());
    }

    private function badges() : string
    {
        return \sprintf(
            '
[![Latest Stable Version](%s/packagist/v/kubawerlos/php-cs-fixer-custom-fixers.svg)](%s)
[![PHP Version](%s/badge/php-%s-8892BF.svg)](https://php.net)
[![License](%s/github/license/kubawerlos/php-cs-fixer-custom-fixers.svg)](%s)
[![Build Status](%s/travis/kubawerlos/php-cs-fixer-custom-fixers/master.svg)](%s)
[![Code coverage](%s/codecov/c/github/kubawerlos/php-cs-fixer-custom-fixers.svg?label=code%%20coverage)](%s)
',
            self::SHIELDS_HOST,
            self::PACKAGIST_URL,
            self::SHIELDS_HOST,
            \rawurlencode($this->composer()->require->php),
            self::SHIELDS_HOST,
            self::PACKAGIST_URL,
            self::SHIELDS_HOST,
            self::TRAVIS_URL,
            self::SHIELDS_HOST,
            self::CODECOV_URL
        );
    }

    private function description() : string
    {
        return \str_replace(
            'PHP CS Fixer',
            '[PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer)',
            $this->composer()->description
        ) . '.';
    }

    private function installation() : string
    {
        return \sprintf(
            '
## Installation
PHP CS Fixer custom fixers can be installed by running:
```bash
composer require --dev %s
```
',
            $this->composer()->name
        );
    }

    private function usage() : string
    {
        return \sprintf(
            '
## Usage
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

    private function fixers() : string
    {
        $output = "\n## Fixers";

        foreach (new Fixers() as $fixer) {
            $reflection = new \ReflectionClass($fixer);

            $originalCode = $fixer->getDefinition()->getCodeSamples()[0]->getCode();
            Tokens::clearCache();
            $tokens = Tokens::fromCode($originalCode);
            $fixer->fix(new StdinFileInfo(), $tokens);
            $fixedCode = $tokens->generateCode();

            $output .= \sprintf(
                "\n- **%s** - %s\n```diff\n%s\n```\n",
                $reflection->getShortName(),
                \lcfirst($fixer->getDefinition()->getSummary()),
                $this->diff($originalCode, $fixedCode)
            );
        }

        return $output;
    }

    private function composer() : \stdClass
    {
        return \json_decode(\file_get_contents(__DIR__ . '/../../composer.json'));
    }

    private function diff(string $from, string $to) : string
    {
        return \str_replace(
            "@@ @@\n",
            '',
            (new Differ(new UnifiedDiffOutputBuilder('')))->diff(
                $from,
                $to
            )
        );
    }

    private function contributing() : string
    {
        return \sprintf(
            '
## Contributing
Request a feature or report a bug by creating [issue](https://github.com/%s/issues).

Alternatively, fork the repo, develop your changes, regenerate `README.md`:
```bash
src/Readme/run > README.md
```
make sure all checks pass:
```bash
%s
```
and submit a pull request.',
            $this->composer()->name,
            \implode("\n", $this->travisScripts())
        );
    }

    private function travisScripts() : array
    {
        return Yaml::parse(\file_get_contents(__DIR__ . '/../../.travis.yml'))['script'];
    }
}
