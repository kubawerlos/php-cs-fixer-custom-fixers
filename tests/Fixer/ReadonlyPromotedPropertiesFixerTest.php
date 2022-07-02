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
 * @requires PHP 8.1
 */
final class ReadonlyPromotedPropertiesFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertTrue($this->fixer->isRisky());
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array<string>>
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
                    public readonly int $f,
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
                    public readonly int $f,
                ) {}
            }',
        ];
        yield [
            '<?php
                class Foo { public function __construct(public readonly int $x) {} }
                class Bar { public function notConstruct(public int $x) {} }
                class Baz { public function __construct(public readonly int $x) {} }
            ',
            '<?php
                class Foo { public function __construct(public int $x) {} }
                class Bar { public function notConstruct(public int $x) {} }
                class Baz { public function __construct(public int $x) {} }
            ',
        ];
    }
}
