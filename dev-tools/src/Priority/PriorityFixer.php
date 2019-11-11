<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixersDev\Priority;

use PhpCsFixer\Fixer\FixerInterface;

final class PriorityFixer
{
    /** @var string[] */
    private $fixersToRunAfter = [];

    /** @var string[] */
    private $fixersToRunBefore = [];

    public function addFixerToRunAfter(FixerInterface $fixer): void
    {
        $this->fixersToRunAfter[] = (new \ReflectionObject($fixer))->getShortName();
    }

    public function addFixerToRunBefore(FixerInterface $fixer): void
    {
        $this->fixersToRunBefore[] = (new \ReflectionObject($fixer))->getShortName();
    }

    public function getFixersToRunAfter(): array
    {
        return $this->fixersToRunAfter;
    }

    public function getFixersToRunBefore(): array
    {
        return $this->fixersToRunBefore;
    }
}
