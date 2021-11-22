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

use PhpCsFixer\WhitespacesFixerConfig;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\MultilinePromotedPropertiesFixer
 *
 * @requires PHP 8.0
 */
final class MultilinePromotedPropertiesFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertFalse($this->fixer->isRisky());
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->fixer->setWhitespacesConfig(new WhitespacesFixerConfig());
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array<string>>>
     */
    public static function provideFixCases(): iterable
    {
        yield 'single parameter' => [
            '<?php class Foo {
                public function __construct(
                    public int $x
                ) {}
            }',
            '<?php class Foo {
                public function __construct(public int $x) {}
            }',
        ];

        yield 'single parameter with trailing comma' => [
            '<?php class Foo {
                public function __construct(
                    protected int $x,
                ) {}
            }',
            '<?php class Foo {
                public function __construct(protected int $x,) {}
            }',
        ];

        yield 'multiple parameters' => [
            '<?php class Foo {
                public function __construct(
                    private int $x,
                    private int $y,
                    private int $z
                ) {}
            }',
            '<?php class Foo {
                public function __construct(private int $x, private int $y, private int $z) {}
            }',
        ];

        yield 'multiple parameters and only one promoted' => [
            '<?php class Foo {
                public function __construct(
                    int $x,
                    private int $y,
                    int $z
                ) {}
            }',
            '<?php class Foo {
                public function __construct(int $x, private int $y, int $z) {}
            }',
        ];

        yield 'parameters with default values' => [
            '<?php class Foo {
                public function __construct(
                    private array $a = [1, 2, 3, 4],
                    private bool $b = self::DEFAULT_B,
                ) {}
            }',
            '<?php class Foo {
                public function __construct(private array $a = [1, 2, 3, 4], private bool $b = self::DEFAULT_B,) {}
            }',
        ];

        yield 'parameters with attributes' => [
            '<?php class Foo {
                public function __construct(
                    private array $a = [1, 2, 3, 4],
                    #[Bar(1, 2, 3)] private bool $b = self::DEFAULT_B,
                ) {}
            }',
            '<?php class Foo {
                public function __construct(private array $a = [1, 2, 3, 4], #[Bar(1, 2, 3)] private bool $b = self::DEFAULT_B,) {}
            }',
        ];

        yield 'multiple classes' => [
            '<?php
            class ClassWithSinglePromotedProperty {
                public function __construct(
                    private int $foo
                ) {}
            }
            class ClassWithoutConstructor {}
            class ClassWithoutPromotedProperties {
                public function __construct(string $a, string $b) {}
            }
            class ClassWithMultiplePromotedProperties {
                public function __construct(
                    private int $x,
                    private int $y,
                    private int $z
                ) {}
            }',
            '<?php
            class ClassWithSinglePromotedProperty {
                public function __construct(private int $foo) {}
            }
            class ClassWithoutConstructor {}
            class ClassWithoutPromotedProperties {
                public function __construct(string $a, string $b) {}
            }
            class ClassWithMultiplePromotedProperties {
                public function __construct(private int $x, private int $y, private int $z) {}
            }',
        ];
    }
}
