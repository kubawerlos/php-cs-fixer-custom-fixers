<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @deprecated use NoUselessCommentFixer instead
 */
final class NoUselessConstructorCommentFixer extends AbstractFixer implements DeprecatedFixerInterface
{
    /** @var NoUselessCommentFixer */
    private $fixer;

    public function __construct()
    {
        $this->fixer = new NoUselessCommentFixer();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no comment like: "Foo constructor".',
            [new CodeSample('<?php
class Foo {
    /**
     * Foo constructor
     */
    public function __construct() {}
}
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
        return [(new \ReflectionObject($this->fixer))->getShortName()];
    }
}
