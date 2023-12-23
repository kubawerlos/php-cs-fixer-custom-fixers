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
 * @covers \PhpCsFixerCustomFixers\Fixer\ConstructorEmptyBracesFixer
 */
final class ConstructorEmptyBracesFixerTest extends AbstractFixerTestCase
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
        yield 'do not fix non-constructors' => [
            '<?php
            class Foo
            {
                public function notConstructor() {
                }
            }
            function __construct() {
            }
            ',
        ];

        yield 'do not fix when body is not empty' => [
            '<?php class Foo {
                public function __construct() {
                    // non-empty body
                }
            }',
        ];

        yield 'fix space in braces' => [
            '<?php class Foo {
                public function __construct() {}
            }',
            '<?php class Foo {
                public function __construct() {
                }
            }',
        ];

        yield 'fix space between closing parenthesis and opening brace' => [
            '<?php class Foo {
                public function __construct() {}
            }',
            '<?php class Foo {
                public function __construct(){
                }
            }',
        ];

        yield 'fix space when none exists' => [
            '<?php class Foo {
                public function __construct() {}
            }',
            '<?php class Foo {
                public function __construct(){}
            }',
        ];

        yield 'fix spaces in braces and between closing parenthesis and opening brace' => [
            '<?php class Foo {
                public function __construct() {}
            }',
            '<?php class Foo {
                public function __construct()
                {
                }
            }',
        ];

        yield 'fix different casing' => [
            '<?php class Foo {
                public function __CONSTRUCT() {}
            }',
            '<?php class Foo {
                public function __CONSTRUCT()
                {
                }
            }',
        ];

        yield 'fix multiple classes' => [
            '<?php
                class Foo {
                    public function __construct() {}
                }
                class Bar {
                    public function __construct() {}
                }
                class Baz {
                    public function __construct() {
                        $this->initialState = 0;
                    }
                }
                class Qux {}
            ',
            '<?php
                class Foo {
                    public function __construct()
                    {
                    }
                }
                class Bar {
                    public function __construct(){}
                }
                class Baz {
                    public function __construct() {
                        $this->initialState = 0;
                    }
                }
                class Qux {}
            ',
        ];
    }
}
