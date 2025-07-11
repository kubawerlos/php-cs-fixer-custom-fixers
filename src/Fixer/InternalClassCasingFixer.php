<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\Casing\ClassReferenceNameCasingFixer;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @deprecated
 *
 * @no-named-arguments
 */
final class InternalClassCasingFixer extends AbstractFixer implements DeprecatedFixerInterface
{
    private ClassReferenceNameCasingFixer $classReferenceNameCasingFixer;

    public function __construct()
    {
        $this->classReferenceNameCasingFixer = new ClassReferenceNameCasingFixer();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return $this->classReferenceNameCasingFixer->getDefinition();
    }

    public function getPriority(): int
    {
        return $this->classReferenceNameCasingFixer->getPriority();
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $this->classReferenceNameCasingFixer->isCandidate($tokens);
    }

    public function isRisky(): bool
    {
        return $this->classReferenceNameCasingFixer->isRisky();
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $this->classReferenceNameCasingFixer->fix($file, $tokens);
    }

    public function getSuccessorsNames(): array
    {
        return [$this->classReferenceNameCasingFixer->getName()];
    }
}
