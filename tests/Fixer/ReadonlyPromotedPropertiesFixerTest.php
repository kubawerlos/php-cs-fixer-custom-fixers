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
 * @covers \PhpCsFixerCustomFixers\Fixer\ReadonlyPromotedPropertiesFixer
 *
 * @requires PHP >= 8.1
 */
final class ReadonlyPromotedPropertiesFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertRiskiness(true);
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<list<string>>
     */
    public static function provideFixCases(): iterable
    {
        yield [
            '<?php class Foo {
                public function __construct(
                    int $x
                ) {}
            }',
        ];

        yield [
            '<?php class Foo {
                public function __construct(
                    public readonly int $a,
                    protected readonly int $b,
                    private readonly int $c,
                ) {}
            }',
            '<?php class Foo {
                public function __construct(
                    public int $a,
                    protected int $b,
                    private int $c,
                ) {}
            }',
        ];

        yield [
            '<?php class Foo {
                public function __construct(
                    public readonly int $a,
                    readonly public int $b,
                    public readonly int $c,
                    readonly public int $d,
                    public readonly int $e,
                    readonly public int $f,
                    public readonly int $g,
                ) {}
            }',
            '<?php class Foo {
                public function __construct(
                    public readonly int $a,
                    readonly public int $b,
                    public int $c,
                    readonly public int $d,
                    public int $e,
                    readonly public int $f,
                    public readonly int $g,
                ) {}
            }',
        ];

        yield [
            '<?php
                class Foo { public function __construct(public readonly int $x) {} }
                class Bar { public function notConstruct(int $x) {} }
                class Baz { public function __construct(public readonly int $x) {} }
            ',
            '<?php
                class Foo { public function __construct(public int $x) {} }
                class Bar { public function notConstruct(int $x) {} }
                class Baz { public function __construct(public int $x) {} }
            ',
        ];

        yield [
            '<?php class Foo {
                public function __construct(public int $x) {}
                public function doSomething() { $this->x = 42; }
            }',
        ];

        yield [
            '<?php class Foo {
                public function __construct(public readonly int $x) {}
                public function doSomething() { $object->x = 42; }
            }',
            '<?php class Foo {
                public function __construct(public int $x) {}
                public function doSomething() { $object->x = 42; }
            }',
        ];

        yield [
            '<?php class Foo {
                public function __construct(public int $x, public readonly int $y, public int $z) {}
                public function doSomething() { $this->x = 42;  $this->z = 10; }
            }',
            '<?php class Foo {
                public function __construct(public int $x, public int $y, public int $z) {}
                public function doSomething() { $this->x = 42;  $this->z = 10; }
            }',
        ];
    }

    /**
     * @dataProvider provideFix82Cases
     *
     * @requires PHP >= 8.2
     */
    public function testFix82(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<list<string>>
     */
    public static function provideFix82Cases(): iterable
    {
        $template = '<?php %s class C1 { public function __construct(public int $x) {} }';

        foreach (
            [
                'readonly',
                'abstract readonly',
                'final readonly',
                'readonly final',
                'readonly abstract',
            ] as $classModifiers
        ) {
            yield [\sprintf($template, $classModifiers)];
        }

        yield [
            '<?php
                class Foo { public function __construct(public readonly int $x) {} }
                readonly class Bar { public function __construct(int $x) {} }
                class Baz { public function __construct(public readonly int $x) {} }
            ',
            '<?php
                class Foo { public function __construct(public int $x) {} }
                readonly class Bar { public function __construct(int $x) {} }
                class Baz { public function __construct(public int $x) {} }
            ',
        ];
    }
}
