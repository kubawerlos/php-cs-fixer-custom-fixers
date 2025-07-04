<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixersDev\Priority;

use PhpCsFixer\Fixer\FixerInterface;

final class PriorityFixer
{
    private FixerInterface $fixer;

    /** @var list<self> */
    private array $fixersToRunAfter = [];

    /** @var list<self> */
    private array $fixersToRunBefore = [];

    private ?int $priority;

    public function __construct(FixerInterface $fixer, ?int $priority)
    {
        $this->fixer = $fixer;
        $this->priority = $priority;
    }

    public function name(): string
    {
        return \get_class($this->fixer);
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
     * @return list<string>
     */
    public function getFixerToRunAfterNames(): array
    {
        return self::getFixerNames($this->fixersToRunAfter);
    }

    /**
     * @return list<string>
     */
    public function getFixerToRunBeforeNames(): array
    {
        return self::getFixerNames($this->fixersToRunBefore);
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

    /**
     * @param list<self> $priorityFixers
     *
     * @return list<string>
     */
    private static function getFixerNames(array $priorityFixers): array
    {
        $fixers = \array_map(
            static fn (self $priorityFixer): string => (new \ReflectionObject($priorityFixer->fixer))->getShortName(),
            $priorityFixers,
        );

        \sort($fixers);

        return $fixers;
    }
}
