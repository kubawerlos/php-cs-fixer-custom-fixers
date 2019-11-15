<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Analyzer\Analysis;

/**
 * @internal
 */
final class DataProviderAnalysis
{
    /** @var string */
    private $name;

    /** @var int */
    private $nameIndex;

    /** @var int[] */
    private $usageIndices;

    /**
     * @param int[] $usageIndices
     */
    public function __construct(string $name, int $nameIndex, array $usageIndices)
    {
        $this->name = $name;
        $this->nameIndex = $nameIndex;
        $this->usageIndices = $usageIndices;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameIndex(): int
    {
        return $this->nameIndex;
    }

    /**
     * @return int[]
     */
    public function getUsageIndices(): array
    {
        return $this->usageIndices;
    }
}
