<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Analyzer\Analysis;

/**
 * @internal
 */
final class SwitchAnalysis
{
    /** @var int */
    private $casesStart;

    /** @var int */
    private $casesEnd;

    /** @var CaseAnalysis[] */
    private $cases = [];

    /**
     * @param CaseAnalysis[] $cases
     */
    public function __construct(int $casesStart, int $casesEnd, array $cases)
    {
        $this->casesStart = $casesStart;
        $this->casesEnd = $casesEnd;
        $this->cases = $cases;
    }

    public function getCasesStart(): int
    {
        return $this->casesStart;
    }

    public function getCasesEnd(): int
    {
        return $this->casesEnd;
    }

    /**
     * @return CaseAnalysis[]
     */
    public function getCases(): array
    {
        return $this->cases;
    }
}
