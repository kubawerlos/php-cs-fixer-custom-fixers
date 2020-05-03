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

namespace Tests\Analyzer;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\DataProviderAnalysis;
use PhpCsFixerCustomFixers\Analyzer\DataProviderAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\DataProviderAnalyzer
 */
final class DataProviderAnalyzerTest extends TestCase
{
    /**
     * @dataProvider provideGettingDataProvidersCases
     */
    public function testGettingDataProviders(array $expected, string $code, int $startIndex = 0, ?int $endIndex = null): void
    {
        $tokens = Tokens::fromCode($code);
        if ($endIndex === null) {
            $endIndex = $tokens->count() - 1;
        }
        $analyzer = new DataProviderAnalyzer();

        self::assertSame(\serialize($expected), \serialize($analyzer->getDataProviders($tokens, $startIndex, $endIndex)));
    }

    public static function provideGettingDataProvidersCases(): iterable
    {
        yield 'single data provider' => [
            [new DataProviderAnalysis('provider', 28, [11])],
            '<?php class FooTest extends TestCase {
                /**
                 * @dataProvider provider
                 */
                public function testFoo() {}
                public function provider() {}
            }',
        ];

        yield 'single static data provider' => [
            [new DataProviderAnalysis('provider', 30, [11])],
            '<?php class FooTest extends TestCase {
                /**
                 * @dataProvider provider
                 */
                public function testFoo() {}
                public static function provider() {}
            }',
        ];

        yield 'multiple data provider' => [
            [
                new DataProviderAnalysis('provider1', 28, [11]),
                new DataProviderAnalysis('provider2', 39, [11]),
                new DataProviderAnalysis('provider3', 50, [11]),
            ],
            '<?php class FooTest extends TestCase {
                /**
                 * @dataProvider provider1
                 * @dataProvider provider2
                 * @dataProvider provider3
                 */
                public function testFoo() {}
                public function provider1() {}
                public function provider2() {}
                public function provider3() {}
            }',
        ];

        foreach (['abstract', 'final', 'private', 'protected', 'static', '/* private */'] as $modifier) {
            yield \sprintf('test function with %s modifier', $modifier) => [
                [
                    new DataProviderAnalysis('provider1', 54, [37]),
                    new DataProviderAnalysis('provider2', 65, [11]),
                    new DataProviderAnalysis('provider3', 76, [24]),
                ],
                \sprintf('<?php class FooTest extends TestCase {
                    /** @dataProvider provider2 */
                    public function testFoo1() {}
                    /** @dataProvider provider3 */
                    %s function testFoo2() {}
                    /** @dataProvider provider1 */
                    public function testFoo3() {}
                    public function provider1() {}
                    public function provider2() {}
                    public function provider3() {}
                }', $modifier),
            ];
        }

        yield 'not existing data provider used' => [
            [],
            '<?php class FooTest extends TestCase {
                /**
                 * @dataProvider provider
                 */
                public function testFoo() {}
            }',
        ];

        yield 'ignore anonymous function' => [
            [
                new DataProviderAnalysis('provider2', 93, [65]),
            ],
            '<?php class FooTest extends TestCase {
                public function testFoo0() {}
                /**
                 * @dataProvider provider0
                 */
                public function testFoo1()
                {
                    /**
                     * @dataProvider provider1
                     */
                     $f = function ($x, $y) { return $x + $y; };
                }
                    /**
                     * @dataProvider provider2
                     */
                public function testFoo2() {}
                public function provider1() {}
                public function provider2() {}
            }',
        ];
    }
}
