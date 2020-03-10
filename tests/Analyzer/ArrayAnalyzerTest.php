<?php

declare(strict_types = 1);

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
     * @dataProvider provideGettingArrayElementsCases
     */
    public function testGettingArrayElements(array $expected, string $code): void
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new ArrayAnalyzer();

        self::assertSame(\serialize($expected), \serialize($analyzer->getElements($tokens, 5)));
    }

    public static function provideGettingArrayElementsCases(): iterable
    {
        yield [
            [],
            '<?php $a = [];',
        ];

        yield [
            [new ArrayElementAnalysis(null, null, 6, 6)],
            '<?php $a = [42];',
        ];

        yield [
            [new ArrayElementAnalysis(6, 6, 10, 10)],
            '<?php $a = [1 => 42];',
        ];

        yield [
            [new ArrayElementAnalysis(6, 6, 10, 10)],
            '<?php $a = [1 => 42,];',
        ];

        yield [
            [new ArrayElementAnalysis(7, 7, 11, 11)],
            '<?php $a = array(4 => 42);',
        ];

        yield [
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

        yield [
            [
                new ArrayElementAnalysis(7, 13, 17, 25),
                new ArrayElementAnalysis(28, 34, 38, 49),
            ],
            '<?php $a = [
                ("Foo" . "Bar") => $this->getContent("Foo", "Bar"),
                ("Foo" . "Baz") => $this->getContent("Foo", "Baz", false),
            ];',
        ];

        yield [
            [
                new ArrayElementAnalysis(7, 7, 11, 19),
                new ArrayElementAnalysis(22, 22, 26, 35),
            ],
            '<?php $a = [
                1 => [11, 12, 13],
                2 => array(21, 22, 23),
            ];',
        ];

        yield [
            [
                new ArrayElementAnalysis(7, 7, 11, 17),
                new ArrayElementAnalysis(20, 20, 24, 50),
            ],
            '<?php $a = [
                1 => foo(1, 2),
                2 => $bar = function ($x, $y) { return max($x, $y); },
            ];',
        ];

        yield [
            [
                new ArrayElementAnalysis(9, 15, 23, 29),
            ],
            '<?php $a = [
                /* comment 1 */ (1 + 2) /* comment 2 */ => /* comment 3 */ foo(1, 2)/* comment 4 */,  /* comment 5 */
            ];',
        ];

        yield [
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
               "baz" /* TODO: something */ => 1 + 1+ 1,
            ];',
        ];

        yield [
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

        yield [
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
