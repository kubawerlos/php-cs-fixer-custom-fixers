<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixersDev;

use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class OrderedClassElementsFixerWrapper
{
    /** @var OrderedClassElementsFixer */
    private $orderedClassElementsFixer;

    /** @var \ReflectionMethod */
    private $getElements;

    /** @var \ReflectionMethod */
    private $sortElements;

    /** @var \ReflectionMethod */
    private $sortTokens;

    public function __construct()
    {
        $this->orderedClassElementsFixer = new OrderedClassElementsFixer();

        $reflection = new \ReflectionObject($this->orderedClassElementsFixer);

        $this->getElements = $reflection->getMethod('getElements');
        $this->getElements->setAccessible(true);

        $this->sortElements = $reflection->getMethod('sortElements');
        $this->sortElements->setAccessible(true);

        $this->sortTokens = $reflection->getMethod('sortTokens');
        $this->sortTokens->setAccessible(true);
    }

    public function getPriority(): int
    {
        return $this->orderedClassElementsFixer->getPriority();
    }

    public function getElements(Tokens $tokens, int $index): array
    {
        return $this->getElements->invoke($this->orderedClassElementsFixer, $tokens, $index);
    }

    public function sortElements(array $elements): array
    {
        return $this->sortElements->invoke($this->orderedClassElementsFixer, $elements);
    }

    public function sortTokens(Tokens $tokens, int $index, int $endIndex, array $sorted): void
    {
        $this->sortTokens->invoke($this->orderedClassElementsFixer, $tokens, $index, $endIndex, $sorted);
    }
}
