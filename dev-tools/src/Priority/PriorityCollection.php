<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixersDev\Priority;

use PhpCsFixer\Fixer\FixerInterface;
use Tests\PriorityTest;

final class PriorityCollection
{
    /** @var PriorityFixer[] */
    private $fixers = [];

    public static function create(): self
    {
        static $instance;

        if ($instance === null) {
            $instance = new self();

            $priorityTest = new PriorityTest();
            foreach ($priorityTest->providePriorityCases() as [$firstFixer, $secondFixer]) {
                $instance->priorityFixer($firstFixer)->addFixerToRunAfter($secondFixer);
                $instance->priorityFixer($secondFixer)->addFixerToRunBefore($firstFixer);
            }
        }

        return $instance;
    }

    public function hasPriorityFixer(string $name): bool
    {
        return isset($this->fixers[$name]);
    }

    public function getPriorityFixer(string $name): PriorityFixer
    {
        return $this->fixers[$name];
    }

    private function priorityFixer(FixerInterface $fixer): PriorityFixer
    {
        $name = (new \ReflectionObject($fixer))->getShortName();

        if (!isset($this->fixers[$name])) {
            $this->fixers[$name] = new PriorityFixer();
        }

        return $this->fixers[$name];
    }
}
