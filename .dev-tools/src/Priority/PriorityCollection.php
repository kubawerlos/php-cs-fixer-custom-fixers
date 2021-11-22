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
use PhpCsFixer\FixerFactory;
use PhpCsFixerCustomFixers\Fixers;
use Tests\PriorityTest;

final class PriorityCollection
{
    /** @var array<PriorityFixer> */
    private $priorityFixers = [];

    public static function create(): self
    {
        /** @var null|self $instance */
        static $instance;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance;
    }

    public function __construct()
    {
        $fixerFactory = new FixerFactory();
        $fixerFactory->registerBuiltInFixers();
        foreach ($fixerFactory->getFixers() as $fixer) {
            $this->priorityFixers[(new \ReflectionObject($fixer))->getShortName()] = new PriorityFixer($fixer, $fixer->getPriority());
        }
        foreach (new Fixers() as $fixer) {
            $this->priorityFixers[(new \ReflectionObject($fixer))->getShortName()] = new PriorityFixer($fixer, null);
        }

        foreach (PriorityTest::providePriorityCases() as [$firstFixer, $secondFixer]) {
            $this->priorityFixer($firstFixer)->addFixerToRunAfter($this->priorityFixer($secondFixer));
            $this->priorityFixer($secondFixer)->addFixerToRunBefore($this->priorityFixer($firstFixer));
        }

        $anythingChanged = true;
        while ($this->isFixerWithoutPriorityInCollection()) {
            if ($anythingChanged) {
                $anythingChanged = false;
            } else {
                /** @var PriorityFixer $priorityFixer */
                $priorityFixer = $this->getFirstPriorityFixerWithoutPriority();
                $anythingChanged = $priorityFixer->calculatePriority(false);
            }

            foreach ($this->priorityFixers as $priorityFixer) {
                if (!$priorityFixer->hasPriority()) {
                    $anythingChanged = $anythingChanged || $priorityFixer->calculatePriority(true);
                }
            }
        }
    }

    public function getPriorityFixer(string $name): PriorityFixer
    {
        return $this->priorityFixers[$name];
    }

    private function priorityFixer(FixerInterface $fixer): PriorityFixer
    {
        return $this->priorityFixers[(new \ReflectionObject($fixer))->getShortName()];
    }

    private function isFixerWithoutPriorityInCollection(): bool
    {
        return $this->getFirstPriorityFixerWithoutPriority() instanceof PriorityFixer;
    }

    private function getFirstPriorityFixerWithoutPriority(): ?PriorityFixer
    {
        foreach ($this->priorityFixers as $priorityFixer) {
            if (!$priorityFixer->hasPriority()) {
                return $priorityFixer;
            }
        }

        return null;
    }
}
