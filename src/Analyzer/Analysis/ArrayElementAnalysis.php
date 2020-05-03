<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Analyzer\Analysis;

/**
 * @internal
 */
final class ArrayElementAnalysis
{
    /** @var ?int */
    private $keyStartIndex;

    /** @var ?int */
    private $keyEndIndex;

    /** @var int */
    private $valueStartIndex;

    /** @var int */
    private $valueEndIndex;

    public function __construct(?int $keyStartIndex, ?int $keyEndIndex, int $valueStartIndex, int $valueEndIndex)
    {
        $this->keyStartIndex = $keyStartIndex;
        $this->keyEndIndex = $keyEndIndex;
        $this->valueStartIndex = $valueStartIndex;
        $this->valueEndIndex = $valueEndIndex;
    }

    public function getKeyStartIndex(): ?int
    {
        return $this->keyStartIndex;
    }

    public function getKeyEndIndex(): ?int
    {
        return $this->keyEndIndex;
    }

    public function getValueStartIndex(): int
    {
        return $this->valueStartIndex;
    }

    public function getValueEndIndex(): int
    {
        return $this->valueEndIndex;
    }
}
