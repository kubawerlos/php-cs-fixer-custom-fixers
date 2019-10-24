<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixersDev;

use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
final class Fixers implements \IteratorAggregate
{
    public function getIterator(): \Generator
    {
        $finder = Finder::create()
            ->files()
            ->in(__DIR__ . '/Fixer/')
            ->sortByName();

        foreach ($finder as $fileInfo) {
            $className = __NAMESPACE__ . '\\Fixer\\' . $fileInfo->getBasename('.php');
            yield new $className();
        }
    }
}
