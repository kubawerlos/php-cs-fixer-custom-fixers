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

namespace PhpCsFixerCustomFixersDev\Priority;

use PhpCsFixer\Fixer\FixerInterface;

final class PriorityFixer
{
    /** @var FixerInterface */
    private $fixer;

    /** @var self[] */
    private $fixersToRunAfter = [];

    /** @var self[] */
    private $fixersToRunBefore = [];

    /** @var null|int */
    private $priority;

    public function __construct(FixerInterface $fixer, ?int $priority)
    {
        $this->fixer = $fixer;
        $this->priority = $priority;
    }

    public function addFixerToRunAfter(self $priorityFixer): void
    {
        $this->fixersToRunAfter[] = $priorityFixer;
    }

    public function addFixerToRunBefore(self $priorityFixer): void
    {
        $this->fixersToRunBefore[] = $priorityFixer;
    }

    public function hasPriority(): bool
    {
        return $this->priority !== null;
    }

    public function getPriority(): int
    {
        if ($this->priority === null) {
            throw new \Exception(\sprintf('Fixer %s has not priority calculated', $this->fixer->getName()));
        }

        return $this->priority;
    }

    /**
     * @return string[]
     */
    public function getFixerToRunAfterNames(): array
    {
        return $this->getFixerNames($this->fixersToRunAfter);
    }

    /**
     * @return string[]
     */
    public function getFixerToRunBeforeNames(): array
    {
        return $this->getFixerNames($this->fixersToRunBefore);
    }

    /**
     * @param self[] $priorityFixers
     *
     * @return string[]
     */
    private function getFixerNames(array $priorityFixers): array
    {
        $fixers = \array_map(
            static function (self $priorityFixer): string {
                return (new \ReflectionObject($priorityFixer->fixer))->getShortName();
            },
            $priorityFixers
        );

        \sort($fixers);

        return $fixers;
    }

    public function calculatePriority(bool $requireAllRelationHavePriority): bool
    {
        $priority = 0;
        foreach ($this->fixersToRunBefore as $priorityFixer) {
            if (!$priorityFixer->hasPriority()) {
                if ($requireAllRelationHavePriority) {
                    return false;
                }
                continue;
            }
            $priority = \min($priority, $priorityFixer->getPriority() - 1);
        }
        foreach ($this->fixersToRunAfter as $priorityFixer) {
            if (!$priorityFixer->hasPriority()) {
                if ($requireAllRelationHavePriority) {
                    return false;
                }
                continue;
            }
            $priority = \max($priority, $priorityFixer->getPriority() + 1);
        }
        $this->priority = $priority;

        return true;
    }
}
