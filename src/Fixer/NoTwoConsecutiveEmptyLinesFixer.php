<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @deprecated use "no_extra_blank_lines" instead
 */
final class NoTwoConsecutiveEmptyLinesFixer extends AbstractFixer implements DeprecatedFixerInterface
{
    /** @var FixerInterface */
    private $fixer;

    public function __construct()
    {
        $this->fixer = new \PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no two consecutive empty lines in code.',
            [new CodeSample('<?php
namespace Foo;


class Bar {};
')]
        );
    }

    public function getPriority(): int
    {
        return $this->fixer->getPriority();
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $this->fixer->isCandidate($tokens);
    }

    public function isRisky(): bool
    {
        return $this->fixer->isRisky();
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $this->fixer->fix($file, $tokens);
    }

    /**
     * @return string[]
     */
    public function getSuccessorsNames(): array
    {
        return [$this->fixer->getName()];
    }
}
