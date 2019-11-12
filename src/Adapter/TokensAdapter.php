<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Adapter;

use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class TokensAdapter extends \SplFixedArray
{
    /** @var Tokens */
    private $tokens;

    public function __construct(Tokens $tokens)
    {
        $this->tokens = $tokens;
        parent::__construct($this->tokens->count());
    }

    public function clearAt(int $index): void
    {
        $this->tokens->clearAt($index);
    }

    public function clearRange(int $indexStart, int $indexEnd): void
    {
        $this->tokens->clearRange($indexStart, $indexEnd);
    }

    public function clearTokenAndMergeSurroundingWhitespace(int $index): void
    {
        $this->tokens->clearTokenAndMergeSurroundingWhitespace($index);
    }

    public function count(): int
    {
        return $this->tokens->count();
    }

    public function findBlockEnd(int $type, int $searchIndex): int
    {
        return $this->tokens->findBlockEnd($type, $searchIndex);
    }

    public function getNextMeaningfulToken(int $index): int
    {
        $nextIndex = $this->tokens->getNextMeaningfulToken($index);
        \assert(\is_int($nextIndex));

        return $nextIndex;
    }

    public function getNextNonWhitespace(int $index): int
    {
        $nextIndex = $this->tokens->getNextNonWhitespace($index);
        \assert(\is_int($nextIndex));

        return $nextIndex;
    }

    public function getNextTokenNonEmpty(int $index): ?int
    {
        return $this->tokens->getNonEmptySibling($index, 1);
    }

    public function getNextTokenNotOfKind(int $index, array $tokens): ?int
    {
        return $this->tokens->getTokenNotOfKindSibling($index, 1, $tokens);
    }

    public function getNextTokenOfKind(int $index, array $tokens): int
    {
        $nextIndex = $this->tokens->getNextTokenOfKind($index, $tokens);
        \assert(\is_int($nextIndex));

        return $nextIndex;
    }

    public function getPrevMeaningfulToken(int $index): int
    {
        $prevIndex = $this->tokens->getPrevMeaningfulToken($index);
        \assert(\is_int($prevIndex));

        return $prevIndex;
    }

    public function getPrevTokenNonEmpty(int $index): int
    {
        $prevIndex = $this->tokens->getNonEmptySibling($index, -1);
        \assert(\is_int($prevIndex));

        return $prevIndex;
    }

    public function getPrevTokenNotOfKind(int $index, array $tokens): int
    {
        $prevIndex = $this->tokens->getTokenNotOfKindSibling($index, -1, $tokens);
        \assert(\is_int($prevIndex));

        return $prevIndex;
    }

    public function getPrevTokenOfKind(int $index, array $tokens): int
    {
        $prevIndex = $this->tokens->getPrevTokenOfKind($index, $tokens);
        \assert(\is_int($prevIndex));

        return $prevIndex;
    }

    public function hasNextMeaningfulToken(int $index): bool
    {
        return $this->tokens->getNextMeaningfulToken($index) !== null;
    }

    public function insertAt(int $index, $items): void
    {
        $this->tokens->insertAt($index, $items);
    }

    public function offsetGet($index): Token
    {
        return $this->tokens->offsetGet($index);
    }

    public function offsetSet($index, $value): void
    {
        $this->tokens->offsetSet($index, $value);
    }

    public function overrideRange(int $indexStart, int $indexEnd, $items): void
    {
        $this->tokens->overrideRange($indexStart, $indexEnd, $items);
    }

    /**
     * @return array<int, Token>
     */
    public function toArray(): array
    {
        return $this->tokens->toArray();
    }

    public function tokens(): Tokens
    {
        return $this->tokens;
    }
}
