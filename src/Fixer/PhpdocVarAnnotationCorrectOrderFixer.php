<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @deprecated use "phpdoc_var_annotation_correct_order" instead
 */
final class PhpdocVarAnnotationCorrectOrderFixer extends AbstractFixer implements DeprecatedFixerInterface
{
    /** @var \PhpCsFixer\Fixer\Phpdoc\PhpdocVarAnnotationCorrectOrderFixer */
    private $fixer;

    public function __construct()
    {
        $this->fixer = new \PhpCsFixer\Fixer\Phpdoc\PhpdocVarAnnotationCorrectOrderFixer();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            $this->fixer->getDefinition()->getSummary(),
            $this->fixer->getDefinition()->getCodeSamples()
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
