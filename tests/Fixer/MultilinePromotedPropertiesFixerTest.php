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
 * @phpstan-import-type _InputConfig from \PhpCsFixer\Fixer\FunctionNotation\MultilinePromotedPropertiesFixer
 *
 * @requires PHP >= 8.0
 */
final class MultilinePromotedPropertiesFixerTest extends AbstractFixerTestCase
{
    public function testSuccessorName(): void
    {
        self::assertSuccessorName('multiline_promoted_properties');
    }

    public function testConfiguration(): void
    {
        $options = self::getConfigurationOptions();
        self::assertArrayHasKey(0, $options);
        self::assertSame('keep_blank_lines', $options[0]->getName());
        self::assertFalse($options[0]->getDefault());
        self::assertSame('minimum_number_of_parameters', $options[1]->getName());
        self::assertSame(1, $options[1]->getDefault());
    }

    public function testIsRisky(): void
    {
        self::assertRiskiness(false);
    }

    /**
     * @param _InputConfig $configuration
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, array $configuration = [], ?WhitespacesFixerConfig $whitespacesFixerConfig = null): void
    {
        $this->doTest($expected, $input, $configuration, $whitespacesFixerConfig);
    }

    /**
     * @return iterable<array{0: string, 1: null|string, 2?: _InputConfig, 3?: WhitespacesFixerConfig}>
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

        yield '0 parameters with 0 configured' => [
            '<?php class Foo {
                    public function __construct() {}
                }',
            null,
            ['minimum_number_of_parameters' => 0],
        ];

        foreach ([0, 1, 2] as $numberOfParameters) {
            yield \sprintf('2 parameters with %d configured', $numberOfParameters) => [
                '<?php class Foo {
                    public function __construct(
                        private int $x,
                        private int $y
                    ) {}
                }',
                '<?php class Foo {
                    public function __construct(private int $x, private int $y) {}
                }',
                ['minimum_number_of_parameters' => $numberOfParameters],
            ];
            yield \sprintf('2 parameters and only one promoted with %d configured', $numberOfParameters) => [
                '<?php class Foo {
                    public function __construct(
                        int $x,
                        private int $y
                    ) {}
                }',
                '<?php class Foo {
                    public function __construct(int $x, private int $y) {}
                }',
                ['minimum_number_of_parameters' => $numberOfParameters],
            ];
        }

        foreach ([3, 4] as $numberOfParameters) {
            yield \sprintf('2 parameters with %d configured', $numberOfParameters) => [
                '<?php class Foo {
                    public function __construct(private int $x, private int $y) {}
                }',
                null,
                ['minimum_number_of_parameters' => $numberOfParameters],
            ];
            yield \sprintf('2 parameters and only one promoted with %d configured', $numberOfParameters) => [
                '<?php class Foo {
                    public function __construct(int $x, private int $y) {}
                }',
                null,
                ['minimum_number_of_parameters' => $numberOfParameters],
            ];
        }

        foreach ([1, 2, 3, 4] as $numberOfParameters) {
            yield \sprintf('2 parameters and none promoted with %d configured', $numberOfParameters) => [
                '<?php class Foo {
                    public function __construct(int $x, int $y) {}
                }',
                null,
                ['minimum_number_of_parameters' => $numberOfParameters],
            ];
        }

        yield 'blank lines removed' => [
            '<?php class Foo {
                public function __construct(
                    private int $x,
                    private int $y,
                    private int $z
                ) {}
            }',
            '<?php class Foo {
                public function __construct(
                    private int $x,

                    private int $y,

                    private int $z
                ) {}
            }',
        ];

        yield 'blank lines kept' => [
            '<?php class Foo {
                public function __construct(
                    private int $x,
                    private int $y,

                    private int $z
                ) {}
            }',
            '<?php class Foo {
                public function __construct(
                    private int $x, private int $y,

                    private int $z
                ) {}
            }',
            ['keep_blank_lines' => true],
        ];

        yield '2 spaces intent and windows line endings' => [
            \str_replace("\n", "\r\n", '<?php class Foo {
              public function __construct(
                public int $x
              ) {}
            }'),
            \str_replace("\n", "\r\n", '<?php class Foo {
              public function __construct(public int $x) {}
            }'),
            [],
            new WhitespacesFixerConfig('  ', "\r\n"),
        ];
    }
}
