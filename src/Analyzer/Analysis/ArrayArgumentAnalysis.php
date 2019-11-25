<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Analyzer\Analysis;

/**
 * @internal
 */
final class ArrayArgumentAnalysis
{
    /** @var ?int */
    private $keyStartIndex;

    /** @var ?int */
    private $keyEndIndex;

    /** @var int */
    private $argumentStartIndex;

    /** @var int */
    private $argumentEndIndex;

    public function __construct(?int $keyStartIndex, ?int $keyEndIndex, int $argumentStartIndex, int $argumentEndIndex)
    {
        $this->keyStartIndex = $keyStartIndex;
        $this->keyEndIndex = $keyEndIndex;
        $this->argumentStartIndex = $argumentStartIndex;
        $this->argumentEndIndex = $argumentEndIndex;
    }

    public function getKeyStartIndex(): ?int
    {
        return $this->keyStartIndex;
    }

    public function getKeyEndIndex(): ?int
    {
        return $this->keyEndIndex;
    }

    public function getArgumentStartIndex(): int
    {
        return $this->argumentStartIndex;
    }

    public function getArgumentEndIndex(): int
    {
        return $this->argumentEndIndex;
    }
}
