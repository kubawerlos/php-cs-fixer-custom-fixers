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

use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\Phpdoc\PhpdocListTypeFixer;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @deprecated
 *
 * @no-named-arguments
 */
final class PhpdocTypeListFixer extends AbstractFixer implements DeprecatedFixerInterface
{
    private PhpdocListTypeFixer $phpdocListTypeFixer;

    public function __construct()
    {
        $this->phpdocListTypeFixer = new PhpdocListTypeFixer();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return $this->phpdocListTypeFixer->getDefinition();
    }

    /**
     * Must run before PhpdocAlignFixer, PhpdocTypesOrderFixer.
     * Must run after CommentToPhpdocFixer, PhpdocArrayStyleFixer.
     */
    public function getPriority(): int
    {
        return $this->phpdocListTypeFixer->getPriority();
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $this->phpdocListTypeFixer->isCandidate($tokens);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $this->phpdocListTypeFixer->fix($file, $tokens);
    }

    public function getSuccessorsNames(): array
    {
        return [$this->phpdocListTypeFixer->getName()];
    }
}
