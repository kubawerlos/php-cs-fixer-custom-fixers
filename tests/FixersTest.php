<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixerCustomFixers\Fixers;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixers
 */
final class FixersTest extends TestCase
{
    public function testCollectionIsSortedByName(): void
    {
        $fixerNames = self::fixerNamesFromCollection();

        $sortedFixerNames = $fixerNames;
        \sort($sortedFixerNames);

        self::assertSame($sortedFixerNames, $fixerNames);
    }

    /**
     * @dataProvider provideFixerIsInCollectionCases
     */
    public function testFixerIsInCollection(FixerInterface $fixer): void
    {
        self::assertContains($fixer->getName(), self::fixerNamesFromCollection());
    }

    /**
     * @return iterable<list<FixerInterface>>
     */
    public static function provideFixerIsInCollectionCases(): iterable
    {
        $finder = Finder::create()
            ->files()
            ->in(__DIR__ . '/../src/Fixer/')
            ->notName('Abstract*Fixer.php');

        foreach ($finder as $file) {
            $className = 'PhpCsFixerCustomFixers\\Fixer\\' . $file->getBasename('.php');

            $fixer = new $className();
            \assert($fixer instanceof FixerInterface);

            yield $className => [$fixer];
        }
    }

    /**
     * @return list<string>
     */
    private static function fixerNamesFromCollection(): array
    {
        return \array_map(
            static fn (FixerInterface $fixer): string => $fixer->getName(),
            \iterator_to_array(new Fixers(), false),
        );
    }
}
