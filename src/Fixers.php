<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers;

use Symfony\Component\Finder\Finder;

final class Fixers implements \IteratorAggregate
{
    public function getIterator(): \Generator
    {
        $finder = Finder::create()
            ->files()
            ->in(__DIR__ . '/Fixer/')
            ->notName('AbstractFixer.php')
            ->notName('DeprecatingFixerInterface.php')
            ->sortByName();

        foreach ($finder as $fileInfo) {
            $className = __NAMESPACE__ . '\\Fixer\\' . $fileInfo->getBasename('.php');
            yield new $className();
        }
    }
}
