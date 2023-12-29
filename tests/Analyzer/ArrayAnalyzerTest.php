<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Analyzer;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ArrayElementAnalysis;
use PhpCsFixerCustomFixers\Analyzer\ArrayAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\ArrayAnalyzer
 */
final class ArrayAnalyzerTest extends TestCase
{
    public function testForNotArray(): void
    {
        $analyzer = new ArrayAnalyzer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Index 3 is not an array.');

        $analyzer->getElements(Tokens::fromCode('<?php $a;$b;$c;'), 3);
    }

    /**
     * @param list<ArrayElementAnalysis> $expected
     *
     * @dataProvider provideGettingArrayElementsCases
     */
    public function testGettingArrayElements(array $expected, string $code): void
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new ArrayAnalyzer();

        self::assertSame(\serialize($expected), \serialize($analyzer->getElements($tokens, 5)));
    }

    /**
     * @return iterable<array{list<ArrayElementAnalysis>, string}>
     */
    public static function provideGettingArrayElementsCases(): iterable
    {
        yield 'empty array' => [
            [],
            '<?php $a = [];',
        ];

        yield 'single element without index' => [
            [new ArrayElementAnalysis(null, null, 6, 6)],
            '<?php $a = [42];',
        ];

        yield 'single element with index' => [
            [new ArrayElementAnalysis(6, 6, 10, 10)],
            '<?php $a = [1 => 42];',
        ];

        yield 'single element with index and trailing comma' => [
            [new ArrayElementAnalysis(6, 6, 10, 10)],
            '<?php $a = [1 => 42,];',
        ];

        yield 'long syntax array' => [
            [new ArrayElementAnalysis(7, 7, 11, 11)],
            '<?php $a = array(4 => 42);',
        ];

        yield 'multiline array' => [
            [
                new ArrayElementAnalysis(7, 7, 11, 11),
                new ArrayElementAnalysis(14, 14, 18, 18),
                new ArrayElementAnalysis(21, 21, 25, 25),
                new ArrayElementAnalysis(28, 28, 32, 32),
            ],
            '<?php $a = [
                1 => 1,
                2 => 2,
                3 => 3,
                4 => 4
            ];',
        ];

        yield 'expressions as keys' => [
            [
                new ArrayElementAnalysis(7, 13, 17, 25),
                new ArrayElementAnalysis(28, 34, 38, 49),
            ],
            '<?php $a = [
                ("Foo" . "Bar") => $this->getContent("Foo", "Bar"),
                ("Foo" . "Baz") => $this->getContent("Foo", "Baz", false),
            ];',
        ];

        yield 'mixed short and long syntax' => [
            [
                new ArrayElementAnalysis(7, 7, 11, 19),
                new ArrayElementAnalysis(22, 22, 26, 35),
            ],
            '<?php $a = [
                1 => [11, 12, 13],
                2 => array(21, 22, 23),
            ];',
        ];

        yield 'values having commas' => [
            [
                new ArrayElementAnalysis(7, 7, 11, 17),
                new ArrayElementAnalysis(20, 20, 24, 50),
            ],
            '<?php $a = [
                1 => foo(1, 2),
                2 => $bar = function ($x, $y) { return max($x, $y); },
            ];',
        ];

        yield 'key and value surrounded by comments in single-line array' => [
            [
                new ArrayElementAnalysis(9, 15, 23, 29),
            ],
            '<?php $a = [
                /* comment 1 */ (1 + 2) /* comment 2 */ => /* comment 3 */ foo(1, 2)/* comment 4 */,  /* comment 5 */
            ];',
        ];

        yield 'key and value surrounded by comments in multi-line array' => [
            [
                new ArrayElementAnalysis(9, 9, 13, 13),
                new ArrayElementAnalysis(18, 18, 22, 26),
                new ArrayElementAnalysis(31, 31, 37, 44),
            ],
            '<?php $a = [
               // foo
               "foo" => 1,
               // bar
               "bar" => 1 + 1,
               // baz
               "baz" /* something */ => 1 + 1+ 1,
            ];',
        ];

        yield 'expressions not wrapped in parenthesis as keys' => [
            [
                new ArrayElementAnalysis(7, 11, 15, 19),
                new ArrayElementAnalysis(22, 26, 30, 34),
            ],
            '<?php $a = [
                    1 + 2 => 3 + 4,
                    5 + 6 => 7 + 8,
                ];
            ',
        ];

        yield 'arrays as values' => [
            [
                new ArrayElementAnalysis(null, null, 7, 16),
                new ArrayElementAnalysis(null, null, 19, 28),
            ],
            '<?php $a = [
                [
                    "foo" => "bar",
                ],
                [
                    "foo" => "bar",
                ],
            ];',
        ];
    }
}
