<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\FunctionParameterSeparationFixer
 */
final class FunctionParameterSeparationFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertRiskiness(false);
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFixCases(): iterable
    {
        yield 'do not change parameters without attribute or hook' => [
            <<<'PHP'
                <?php
                function foo(
                    int $x,
                    int $y,
                    int $z,
                ) {}
                PHP,
        ];

        yield 'do not change single parameter' => [
            <<<'PHP'
                <?php
                function foo(
                    #[AnAttribute]
                    int $x,
                ) {}
                PHP,
        ];

        yield 'do not change in attribute' => [
            <<<'PHP'
                <?php
                function foo(
                    #[AnAttribute([
                        1,
                        2,
                    ])]
                    int $x,
                ) {}
                PHP,
        ];

        yield 'do not change parameters without whitespaces' => [
            <<<'PHP'
                <?php
                function foo(#[AttributeX]int$x,#[AttributeY1]#[AttributeY2]int$y,#[AttributeZ]int$z) {}
                PHP,
        ];

        yield 'do not change parameters without newline before' => [
            <<<'PHP'
                <?php
                function foo(#[AttributeX]
                    int $x, #[AttributeY1]
                    #[AttributeY2]
                    int $y, #[AttributeZ]
                    int $z,
                ) {}
                PHP,
        ];

        yield 'one parameter with attribute' => [
            <<<'PHP'
                <?php
                function foo(
                    int $a,

                    int $b,

                    #[AnAttribute]
                    int $c,

                    int $d
                ) {}
                PHP,
            <<<'PHP'
                <?php
                function foo(
                    int $a,
                    int $b,
                    #[AnAttribute]
                    int $c,
                    int $d
                ) {}
                PHP,
        ];

        yield 'one parameter with attribute and trailing comma' => [
            <<<'PHP'
                <?php
                function foo(
                    #[AnAttribute]
                    int $a,

                    int $b,
                ) {}
                PHP,
            <<<'PHP'
                <?php
                function foo(
                    #[AnAttribute]
                    int $a,
                    int $b,
                ) {}
                PHP,
        ];

        yield 'mix of same and separate line parameters' => [
            <<<'PHP'
                <?php
                function foo(
                    #[Pair]
                    int $x1, int $y1,

                    #[Pair]
                    int $x2, int $y2,
                ) {}
                PHP,
            <<<'PHP'
                <?php
                function foo(
                    #[Pair]
                    int $x1, int $y1,
                    #[Pair]
                    int $x2, int $y2,
                ) {}
                PHP,
        ];

        yield 'multiple functions' => [
            <<<'PHP'
                <?php
                function foo(
                    #[AnAttribute]
                    int $a,

                    int $b
                ) {}
                function bar(
                    int $a,
                    int $b,
                ) {}
                function baz(
                    int $a,

                    #[AnAttribute]
                    int $b,
                ) {}
                PHP,
            <<<'PHP'
                <?php
                function foo(
                    #[AnAttribute]
                    int $a,
                    int $b
                ) {}
                function bar(
                    int $a,
                    int $b,
                ) {}
                function baz(
                    int $a,
                    #[AnAttribute]
                    int $b,
                ) {}
                PHP,
        ];
    }

    /**
     * @dataProvider provideFix84Cases
     *
     * @requires PHP >= 8.4
     */
    public function testFix84(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFix84Cases(): iterable
    {
        yield 'do not change parameters without whitespaces' => [
            <<<'PHP'
                <?php
                class Foo
                {
                    public function __construct(#[AttributeX]int$x,#[AttributeY1]#[AttributeY2]int$y,#[AttributeZ]int$z) {}
                }
                PHP,
        ];

        yield 'one parameter with hook' => [
            <<<'PHP'
                <?php
                class Foo
                {
                    public function __construct(
                        int $a,

                        int $b { get => 0; },

                        int $c,

                        int $d,
                    ) {}
                }
                PHP,
            <<<'PHP'
                <?php
                class Foo
                {
                    public function __construct(
                        int $a,
                        int $b { get => 0; },
                        int $c,
                        int $d,
                    ) {}
                }
                PHP,
        ];

        yield 'do not change in hook' => [
            <<<'PHP'
                <?php
                class Foo
                {
                    public function __construct(
                        int $a { get => [
                                'foo' => 'bar',
                                'value' => 0,
                            ];
                        },
                    ) {}
                }
                PHP,
        ];
    }
}
