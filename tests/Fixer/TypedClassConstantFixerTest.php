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
        self::assertRiskiness(false);
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

        yield 'array as sum of long syntax array and constant' => [
            '<?php class Foo { public const array BAR = Baz::C + array(1); }',
            '<?php class Foo { public const BAR = Baz::C + array(1); }',
        ];

        yield 'array as sum of short syntax array and constant' => [
            '<?php class Foo { public const array BAR = Baz::C1 + [2] + Baz::C2; }',
            '<?php class Foo { public const BAR = Baz::C1 + [2] + Baz::C2; }',
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

        yield 'integer with exponentiation operator' => [
            '<?php class Foo { public const int BAR = 2 ** 10; }',
            '<?php class Foo { public const BAR = 2 ** 10; }',
        ];

        yield 'integer with shift left operator' => [
            '<?php class Foo { public const int BAR = 1 << 16; }',
            '<?php class Foo { public const BAR = 1 << 16; }',
        ];

        yield 'integer with shift right operator' => [
            '<?php class Foo { public const int BAR = 1024 >> 1; }',
            '<?php class Foo { public const BAR = 1024 >> 1; }',
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

        yield 'values with a leading backslash' => [
            <<<'PHP'
                <?php class Foo {
                    public const false C_FALSE = \false;
                    public const true C_TRUE = \true;
                    public const null C_NULL = \null;
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public const C_FALSE = \false;
                    public const C_TRUE = \true;
                    public const C_NULL = \null;
                }
                PHP,
        ];

        yield 'string with double quotes' => [
            '<?php class Foo { public const string BAR = "Jane Doe"; }',
            '<?php class Foo { public const BAR = "Jane Doe"; }',
        ];

        yield 'string with single quotes' => [
            "<?php class Foo { public const string BAR = 'John Doe'; }",
            "<?php class Foo { public const BAR = 'John Doe'; }",
        ];

        yield 'binary string' => [
            '<?php class Foo { public const string BAR = b"Jane Doe"; }',
            '<?php class Foo { public const BAR = b"Jane Doe"; }',
        ];

        yield 'string with heredoc syntax' => [
            <<<'PHP'
                <?php class Foo {
                    public const string BAR = <<<STRING
                        the content
                    STRING;
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public const BAR = <<<STRING
                        the content
                    STRING;
                }
                PHP,
        ];

        yield 'string with nowdoc syntax' => [
            <<<'PHP'
                <?php class Foo {
                    public const string BAR = <<<'STRING'
                        the content
                    STRING;
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public const BAR = <<<'STRING'
                        the content
                    STRING;
                }
                PHP,
        ];

        yield 'string as reference to other class' => [
            <<<'PHP'
                <?php class Foo {
                    public const string BAR = FooFoo::class;
                    public const mixed BAZ = CONFIG_66 ? FooFoo::class : -1;
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public const BAR = FooFoo::class;
                    public const BAZ = CONFIG_66 ? FooFoo::class : -1;
                }
                PHP,
        ];

        yield 'string as magic constant' => [
            <<<'PHP'
                <?php class Foo {
                    public const string C1 = __CLASS__;
                    public const string C2 = __DIR__;
                    public const string C3 = __FILE__;
                    public const string C4 = __FUNCTION__;
                    public const int C5 = __LINE__;
                    public const float C5_HALF = __LINE__ / 2;
                    public const string C6 = __METHOD__;
                    public const string C7 = __NAMESPACE__;
                    public const string C8 = __TRAIT__;
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public const C1 = __CLASS__;
                    public const C2 = __DIR__;
                    public const C3 = __FILE__;
                    public const C4 = __FUNCTION__;
                    public const C5 = __LINE__;
                    public const C5_HALF = __LINE__ / 2;
                    public const C6 = __METHOD__;
                    public const C7 = __NAMESPACE__;
                    public const C8 = __TRAIT__;
                }
                PHP,
        ];

        yield 'string as result of concatenations with parentheses' => [
            '<?php class Foo { public const string BAR = "A" . 1 . ("B" . 0.25) . "C"; }',
            '<?php class Foo { public const BAR = "A" . 1 . ("B" . 0.25) . "C"; }',
        ];

        yield 'string as result of concatenation with other constant' => [
            '<?php class Foo { public const string BAR = "A" . Baz::C; }',
            '<?php class Foo { public const BAR = "A" . Baz::C; }',
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

        yield 'anonymous class' => [
            '<?php new class () { public const int BAR = 0; };',
            '<?php new class () { public const BAR = 0; };',
        ];

        yield 'unknown other constant' => [
            '<?php class Foo { public const mixed BAR = CONSTANT_FROM_FAR_AWAY; }',
            '<?php class Foo { public const BAR = CONSTANT_FROM_FAR_AWAY; }',
        ];

        yield 'expression of unknown type' => [
            '<?php class Foo { public const mixed BAR = 10 * FLOAT_OR_INTEGER + 3; }',
            '<?php class Foo { public const BAR = 10 * FLOAT_OR_INTEGER + 3; }',
        ];

        yield 'constants that can be of different types' => [
            <<<'PHP'
                <?php class Foo {
                    public const mixed BAR = SHOULD_BE_INT ? 1 : ["one"];
                    public const mixed BAZ = NAME === 'A' . 'b' ? 1 : ["one"];
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public const BAR = SHOULD_BE_INT ? 1 : ["one"];
                    public const BAZ = NAME === 'A' . 'b' ? 1 : ["one"];
                }
                PHP,
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

        yield 'make multiple constants definition mixed type' => [
            '<?php class Foo { public const mixed BAR = 1, BAZ = "two"; }',
            '<?php class Foo { public const BAR = 1, BAZ = "two"; }',
        ];
    }
}
