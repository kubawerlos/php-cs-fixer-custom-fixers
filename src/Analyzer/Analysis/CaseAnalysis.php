<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Analyzer\Analysis;

/**
 * @internal
 */
final class CaseAnalysis
{
    /** @var int */
    private $colonIndex;

    public function __construct(int $colonIndex)
    {
        $this->colonIndex = $colonIndex;
    }

    public function getColonIndex(): int
    {
        return $this->colonIndex;
    }
}
