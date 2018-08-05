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

class ReadmeCommand extends BaseCommand
{
    private const NAME = 'PHP CS Fixer: custom fixers';

    private const SHIELDS_HOST = 'https://img.shields.io';

    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        $output->writeln(\sprintf('# %s', self::NAME));
        $output->writeln($this->badges());
        $output->writeln($this->description());
        $output->writeln($this->installation());
        $output->writeln($this->usage());
        $output->writeln($this->fixers());
        $output->writeln($this->contributing());
    }

    private function badges() : string
    {
        return "\n" . \implode("\n", [
            $this->badge(
                'Latest Stable Version',
                \sprintf('%s/packagist/v/%s.svg', self::SHIELDS_HOST, $this->composer()->name),
                \sprintf('https://packagist.org/packages/%s', $this->composer()->name)
            ),
            $this->badge(
                'PHP Version',
                \sprintf('%s/badge/php-%s-8892BF.svg', self::SHIELDS_HOST, \rawurlencode($this->composer()->require->php)),
                'https://php.net'
            ),
            $this->badge(
                'License',
                \sprintf('%s/github/license/%s.svg', self::SHIELDS_HOST, $this->composer()->name),
                \sprintf('https://packagist.org/packages/%s', $this->composer()->name)
            ),
            $this->badge(
                'Build Status',
                \sprintf('%s/travis/%s/master.svg', self::SHIELDS_HOST, $this->composer()->name),
                \sprintf('https://travis-ci.org/%s', $this->composer()->name)
            ),
            $this->badge(
                'Code coverage',
                \sprintf('%s/coveralls/github/%s/master.svg', self::SHIELDS_HOST, $this->composer()->name),
                \sprintf('https://coveralls.io/github/%s?branch=master', $this->composer()->name)
            ),
        ]) . "\n";
    }

    private function badge(string $description, string $imageUrl, string $targetUrl) : string
    {
        return
            \sprintf(
                '[![%s](%s)](%s)',
                $description,
                $imageUrl,
                $targetUrl
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
%s can be installed by running:
```bash
composer require --dev %s
```
',
            self::NAME,
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

            $output .= \sprintf(
                "\n- **%s** - %s\n",
                $reflection->getShortName(),
                \lcfirst($fixer->getDefinition()->getSummary())
            );

            if ($fixer->isRisky()) {
                $output .= \sprintf(
                    "\n*Risky: %s*\n",
                    \lcfirst($fixer->getDefinition()->getRiskyDescription())
                );
            }

            $originalCode = $fixer->getDefinition()->getCodeSamples()[0]->getCode();
            Tokens::clearCache();
            $tokens = Tokens::fromCode($originalCode);
            $fixer->fix(new StdinFileInfo(), $tokens);
            $fixedCode = $tokens->generateCode();

            $output .= \sprintf(
                "```diff\n%s\n```\n",
                $this->diff($originalCode, $fixedCode)
            );
        }

        return $output;
    }

    private function diff(string $from, string $to) : string
    {
        return \rtrim(\str_replace(
            "@@ @@\n",
            '',
            (new Differ(new UnifiedDiffOutputBuilder('')))->diff(
                $from,
                $to
            )
        ));
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
composer check
```
and submit a pull request.',
            $this->composer()->name
        );
    }

    private function composer() : \stdClass
    {
        return \json_decode(\file_get_contents(__DIR__ . '/../../composer.json'));
    }
}
