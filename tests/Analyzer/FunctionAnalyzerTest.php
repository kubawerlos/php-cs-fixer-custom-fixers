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

namespace Tests\Analyzer;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ArgumentAnalysis;
use PhpCsFixerCustomFixers\Analyzer\FunctionAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\FunctionAnalyzer
 */
final class FunctionAnalyzerTest extends TestCase
{
    /**
     * @dataProvider provideForNotFunctionCases
     */
    public function testForNotFunction(string $code, int $index): void
    {
        $analyzer = new FunctionAnalyzer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Index %d is not a function.', $index));

        $analyzer->getFunctionArguments(Tokens::fromCode($code), $index);
    }

    /**
     * @return iterable<array{string, int}>
     */
    public static function provideForNotFunctionCases(): iterable
    {
        yield ['<?php $a;', 2];
        yield ['<?php foo + bar;', 1];
    }

    /**
     * @param array<ArgumentAnalysis> $expected
     *
     * @dataProvider provideGettingArgumentsCases
     */
    public function testGettingArguments(array $expected, string $code, int $index): void
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new FunctionAnalyzer();

        self::assertSame(\serialize($expected), \serialize($analyzer->getFunctionArguments($tokens, $index)));
    }

    /**
     * @return iterable<array{array<ArgumentAnalysis>, string, int}>
     */
    public static function provideGettingArgumentsCases(): iterable
    {
        yield 'no arguments' => [
            [],
            '<?php foo();',
            1,
        ];

        yield '1 argument' => [
            [new ArgumentAnalysis(3, 3, true)],
            '<?php foo(1);',
            1,
        ];

        yield '3 arguments' => [
            [
                new ArgumentAnalysis(3, 3, true),
                new ArgumentAnalysis(6, 6, true),
                new ArgumentAnalysis(9, 9, true),
            ],
            '<?php foo(1, 2, 3);',
            1,
        ];

        yield 'not constant arguments' => [
            [
                new ArgumentAnalysis(3, 3, true),
                new ArgumentAnalysis(6, 6, false),
                new ArgumentAnalysis(9, 9, true),
                new ArgumentAnalysis(12, 12, false),
            ],
            '<?php foo(1, $x, 4, $y);',
            1,
        ];

        yield 'long arguments' => [
            [
                new ArgumentAnalysis(3, 5, false),
                new ArgumentAnalysis(8, 8, true),
                new ArgumentAnalysis(11, 11, true),
                new ArgumentAnalysis(14, 23, false),
            ],
            '<?php foo(baz(), 1, bar, qux(2, 3, 4));',
            1,
        ];

        yield 'array in arguments' => [
            [
                new ArgumentAnalysis(3, 16, true),
                new ArgumentAnalysis(19, 28, true),
            ],
            '<?php foo([1 => 2, 3 => 4], array(5, 6, 7));',
            1,
        ];

        yield 'multiline arguments' => [
            [
                new ArgumentAnalysis(4, 4, true),
                new ArgumentAnalysis(8, 8, true),
                new ArgumentAnalysis(12, 12, true),
            ],
            '<?php foo(
                1    ,
                2    ,
                3
            );',
            1,
        ];

        if (\PHP_VERSION_ID >= 70300) {
            yield 'argument with trailing comma' => [
                [new ArgumentAnalysis(3, 3, false)],
                '<?php foo($x);',
                1,
            ];

            yield ' multiple arguments with trailing comma' => [
                [
                    new ArgumentAnalysis(3, 3, true),
                    new ArgumentAnalysis(6, 6, true),
                    new ArgumentAnalysis(9, 9, true),
                ],
                '<?php foo(1, 2, 3,);',
                1,
            ];
        }
    }
}
