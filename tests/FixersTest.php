<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixerCustomFixers\Fixers;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixers
 */
final class FixersTest extends TestCase
{
    public function testCollectionIsSortedByName(): void
    {
        $fixerNames = $this->fixerNamesFromCollection();

        $sortedFixerNames = $fixerNames;
        \sort($sortedFixerNames);

        self::assertSame($sortedFixerNames, $fixerNames);
    }

    /**
     * @dataProvider provideFixerIsInCollectionCases
     */
    public function testFixerIsInCollection(FixerInterface $fixer): void
    {
        self::assertContains($fixer->getName(), $this->fixerNamesFromCollection());
    }

    /**
     * @return iterable<array<FixerInterface>>
     */
    public static function provideFixerIsInCollectionCases(): iterable
    {
        return \array_map(
            static function (SplFileInfo $fileInfo): array {
                $className = 'PhpCsFixerCustomFixers\\Fixer\\' . $fileInfo->getBasename('.php');

                /** @var FixerInterface $fixer */
                $fixer = new $className();

                return [$fixer];
            },
            \iterator_to_array(Finder::create()
                ->files()
                ->in(__DIR__ . '/../src/Fixer/')
                ->notName('AbstractFixer.php')
                ->notName('DeprecatingFixerInterface.php')
                ->getIterator())
        );
    }

    /**
     * @return string[]
     */
    private function fixerNamesFromCollection(): array
    {
        return \array_map(
            static function (FixerInterface $fixer): string {
                return $fixer->getName();
            },
            \iterator_to_array(new Fixers())
        );
    }
}
