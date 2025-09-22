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
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @deprecated
 *
 * @no-named-arguments
 */
final class PhpDocPropertySorterFixer extends AbstractFixer implements DeprecatedFixerInterface
{
    private PhpdocPropertySortedFixer $phpdocPropertySortedFixer;

    public function __construct()
    {
        $this->phpdocPropertySortedFixer = new PhpdocPropertySortedFixer();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return $this->phpdocPropertySortedFixer->getDefinition();
    }

    public function getPriority(): int
    {
        return $this->phpdocPropertySortedFixer->getPriority();
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $this->phpdocPropertySortedFixer->isCandidate($tokens);
    }

    public function isRisky(): bool
    {
        return $this->phpdocPropertySortedFixer->isRisky();
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $this->phpdocPropertySortedFixer->fix($file, $tokens);
    }

    /**
     * @return list<string>
     */
    public function getSuccessorsNames(): array
    {
        return [$this->phpdocPropertySortedFixer->getName()];
    }
}
