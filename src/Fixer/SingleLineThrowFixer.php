<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\TokensAdapter;

/**
 * @deprecated use "single_line_throw" instead
 */
final class SingleLineThrowFixer extends AbstractFixer implements DeprecatedFixerInterface
{
    /** @var \PhpCsFixer\Fixer\FunctionNotation\SingleLineThrowFixer */
    private $fixer;

    public function __construct()
    {
        $this->fixer = new \PhpCsFixer\Fixer\FunctionNotation\SingleLineThrowFixer();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return $this->fixer->getDefinition();
    }

    public function getPriority(): int
    {
        // must be run before ConcatSpaceFixer and NoUnneededConcatenationFixer
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

    /**
     * @return string[]
     */
    public function getSuccessorsNames(): array
    {
        return [$this->fixer->getName()];
    }

    protected function applyFix(\SplFileInfo $file, TokensAdapter $tokens): void
    {
        $this->fixer->fix($file, $tokens->tokens());
    }
}
