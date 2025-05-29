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
 * @covers \PhpCsFixerCustomFixers\Fixer\TypedClassConstantFixer
 */
final class TypedClassConstantFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertRiskiness(true);
    }

    /**
     * @dataProvider provideFixCases
     *
     * @requires PHP >= 8.3
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
        yield 'non-class constants are ignored' => ['<?php const FOO = 1;'];

        yield 'array with long syntax' => [
            '<?php class Foo { public const array BAR = array(true, 1, "foo"); }',
            '<?php class Foo { public const BAR = array(true, 1, "foo"); }',
        ];

        yield 'array with short syntax' => [
            '<?php class Foo { public const array BAR = [true, 1, "foo"]; }',
            '<?php class Foo { public const BAR = [true, 1, "foo"]; }',
        ];

        yield 'array as result of an expression' => [
            '<?php class Foo { public const array BAR = [1 => 2] + array(3 => 4) + [5 => 6]; }',
            '<?php class Foo { public const BAR = [1 => 2] + array(3 => 4) + [5 => 6]; }',
        ];

        yield 'false' => [
            '<?php class Foo { public const false BAR = false; }',
            '<?php class Foo { public const BAR = false; }',
        ];

        yield 'true' => [
            '<?php class Foo { public const true BAR = true; }',
            '<?php class Foo { public const BAR = true; }',
        ];

        yield 'integer' => [
            '<?php class Foo { public const int BAR = 123; }',
            '<?php class Foo { public const BAR = 123; }',
        ];

        yield 'integer as result of an expression' => [
            '<?php class Foo { public const int BAR = 1 + 2 - 3 * 4; }',
            '<?php class Foo { public const BAR = 1 + 2 - 3 * 4; }',
        ];

        yield 'integer as result of an expression with parentheses' => [
            '<?php class Foo { public const int BAR = 1000 * (701 + 22); }',
            '<?php class Foo { public const BAR = 1000 * (701 + 22); }',
        ];

        yield 'float' => [
            '<?php class Foo { public const float BAR = 2.5; }',
            '<?php class Foo { public const BAR = 2.5; }',
        ];

        yield 'float as result of expression' => [
            '<?php class Foo { public const float BAR = 1 + (2 - 3) * 4 / 5; }',
            '<?php class Foo { public const BAR = 1 + (2 - 3) * 4 / 5; }',
        ];

        yield 'null' => [
            '<?php class Foo { public const null BAR = null; }',
            '<?php class Foo { public const BAR = null; }',
        ];

        yield 'NULL' => [
            '<?php class Foo { public const null BAR = NULL; }',
            '<?php class Foo { public const BAR = NULL; }',
        ];

        yield 'string with double quotes' => [
            '<?php class Foo { public const string BAR = "Jane Doe"; }',
            '<?php class Foo { public const BAR = "Jane Doe"; }',
        ];

        yield 'string with single quotes' => [
            "<?php class Foo { public const string BAR = 'John Doe'; }",
            "<?php class Foo { public const BAR = 'John Doe'; }",
        ];

        yield 'string as result of concatenation' => [
            '<?php class Foo { public const string BAR = "A" . 1 . "B" . 0.25 . "C"; }',
            '<?php class Foo { public const BAR = "A" . 1 . "B" . 0.25 . "C"; }',
        ];

        yield 'multiple constants' => [
            <<<'PHP'
                <?php
                class Foo {
                    public const int ONE = 1;
                    protected const bool|float ALREADY_TYPED = true;
                    private const string NAME = 'name';
                }
                const NOT_CLASS = true;
                class Bar {
                    const array ARRAY_LONG_SYNTAX = array();
                    const array ARRAY_SHORT_SYNTAX = [];
                    const string lowercased_name = 'lowercased_name';
                }
                PHP,
            <<<'PHP'
                <?php
                class Foo {
                    public const ONE = 1;
                    protected const bool|float ALREADY_TYPED = true;
                    private const NAME = 'name';
                }
                const NOT_CLASS = true;
                class Bar {
                    const ARRAY_LONG_SYNTAX = array();
                    const ARRAY_SHORT_SYNTAX = [];
                    const lowercased_name = 'lowercased_name';
                }
                PHP,
        ];

        yield 'unknown other constant' => [
            '<?php class Foo { public const mixed BAR = CONSTANT_FROM_FAR_AWAY; }',
            '<?php class Foo { public const BAR = CONSTANT_FROM_FAR_AWAY; }',
        ];

        yield 'expression of unknown type' => [
            '<?php class Foo { public const mixed BAR = 10 * FLOAT_OR_INTEGER + 3; }',
            '<?php class Foo { public const BAR = 10 * FLOAT_OR_INTEGER + 3; }',
        ];

        yield 'constant that can be of different types' => [
            '<?php class Foo { public const mixed BAR = SHOULD_BE_INT ? 1 : "one"; }',
            '<?php class Foo { public const BAR = SHOULD_BE_INT ? 1 : "one"; }',
        ];

        yield 'constant that can be of different types - more complex case' => [
            <<<'PHP'
                <?php
                class HellCoreServiceManagerHelper
                {
                    const mixed OPTION_666__YES__1010011010_VALUE_4_1_3
                        = IS_OVERRIDEN_BY_BEELZEBOSS
                            ? "Hell yeah"
                            : CIRCLES_MANAGER_ACCESS === [0o1232, 'super_manager', false, -66.6]
                                ? true
                                : HellComponent443556::SHOULDNT_NOT_BE_DIFFERENT_THAN_NULL
                                    ? null
                                    : 0.001;
                }
                PHP,
            <<<'PHP'
                <?php
                class HellCoreServiceManagerHelper
                {
                    const OPTION_666__YES__1010011010_VALUE_4_1_3
                        = IS_OVERRIDEN_BY_BEELZEBOSS
                            ? "Hell yeah"
                            : CIRCLES_MANAGER_ACCESS === [0o1232, 'super_manager', false, -66.6]
                                ? true
                                : HellComponent443556::SHOULDNT_NOT_BE_DIFFERENT_THAN_NULL
                                    ? null
                                    : 0.001;
                }
                PHP,
        ];
    }
}
