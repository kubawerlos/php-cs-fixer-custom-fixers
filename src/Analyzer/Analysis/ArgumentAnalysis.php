<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Analyzer\Analysis;

/**
 * @internal
 */
final class ArgumentAnalysis
{
    /** @var int */
    private $startIndex;

    /** @var int */
    private $endIndex;

    /** @var bool */
    private $isConstant;

    public function __construct(int $startIndex, int $endIndex, bool $isConstant)
    {
        $this->startIndex = $startIndex;
        $this->endIndex = $endIndex;
        $this->isConstant = $isConstant;
    }

    public function getStartIndex(): int
    {
        return $this->startIndex;
    }

    public function getEndIndex(): int
    {
        return $this->endIndex;
    }

    public function isConstant(): bool
    {
        return $this->isConstant;
    }
}
