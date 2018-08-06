<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers;

use Symfony\Component\Finder\Finder;

final class Fixers implements \IteratorAggregate
{
    public function getIterator(): \ArrayIterator
    {
        $finder = Finder::create()
            ->files()
            ->in(__DIR__ . '/Fixer/')
            ->notName('AbstractFixer.php')
            ->sortByName();

        $arrayIterator = new \ArrayIterator();

        foreach ($finder as $fileInfo) {
            $className = __NAMESPACE__ . '\\Fixer\\' . $fileInfo->getBasename('.php');
            $arrayIterator->append(new $className());
        }

        return $arrayIterator;
    }
}
