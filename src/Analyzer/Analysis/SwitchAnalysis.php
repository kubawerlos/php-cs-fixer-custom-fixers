<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Analyzer\Analysis;

/**
 * @internal
 */
final class SwitchAnalysis
{
    /** @var int */
    private $curlyBracesStart;

    /** @var int */
    private $curlyBracesEnd;

    /** @var CaseAnalysis[] */
    private $cases = [];

    /**
     * @param CaseAnalysis[] $cases
     */
    public function __construct(int $curlyBracesStart, int $curlyBracesEnd, array $cases)
    {
        $this->curlyBracesStart = $curlyBracesStart;
        $this->curlyBracesEnd = $curlyBracesEnd;
        $this->cases = $cases;
    }

    public function getCurlyBracesStart(): int
    {
        return $this->curlyBracesStart;
    }

    public function getCurlyBracesEnd(): int
    {
        return $this->curlyBracesEnd;
    }

    /**
     * @return CaseAnalysis[]
     */
    public function getCases(): array
    {
        return $this->cases;
    }
}
