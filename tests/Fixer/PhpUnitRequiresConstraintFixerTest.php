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
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpUnitRequiresConstraintFixer
 */
final class PhpUnitRequiresConstraintFixerTest extends AbstractFixerTestCase
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
        yield 'PHPDoc on variable before class' => [<<<'PHP'
            <?php
            /**
             * The file @requires PHP 8.4
             */
            $minPHPVersion = '8.4';
            /**
             * Foo test starts here
             */
            class FooTest extends TestCase {
                public function testFoo() {}
            }
            PHP];

        yield 'PHPDoc on property' => [<<<'PHP'
            <?php
            class FooTest extends TestCase {
                /**
                 * @requires PHP 8.4
                 */
                private $prop;
                public function testFoo() {}
            }
            PHP];

        yield 'anonymous class' => [
            <<<'PHP'
                <?php
                class FooTest extends TestCase {
                    /**
                     * @requires PHP >= 8.1
                     */
                    public function testFoo1() {}
                    public function testFoo2() {
                        new class extends TestCase {
                            /**
                             * @requires PHP >= 8.2
                             */
                             public function testX() {}
                        };
                    }
                    /**
                     * @requires PHP >= 8.3
                     */
                    public function testFoo3() {}
                }
                PHP,
            <<<'PHP'
                <?php
                class FooTest extends TestCase {
                    /**
                     * @requires PHP 8.1
                     */
                    public function testFoo1() {}
                    public function testFoo2() {
                        new class extends TestCase {
                            /**
                             * @requires PHP 8.2
                             */
                             public function testX() {}
                        };
                    }
                    /**
                     * @requires PHP 8.3
                     */
                    public function testFoo3() {}
                }
                PHP,
        ];

        foreach (self::getFixCases() as $key => $fixCase) {
            yield '[class] ' . $key => \array_map(
                static fn (string $case): string => \sprintf(
                    <<<'PHP'
                        <?php
                        %s
                        class FooTest extends TestCase {
                            public function testFoo() {}
                        }
                        PHP,
                    $case,
                ),
                $fixCase,
            );
            yield '[method] ' . $key => \array_map(
                static fn (string $case): string => \sprintf(
                    <<<'PHP'
                        <?php
                        class FooTest extends TestCase {
                            %s
                            public function testFoo() {}
                        }
                        PHP,
                    $case,
                ),
                $fixCase,
            );
        }
    }

    /**
     * @dataProvider provideFix80Cases
     *
     * @requires PHP >= 8.0
     */
    public function testFix80(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFix80Cases(): iterable
    {
        yield 'attribute with constraints' => [
            '<?php class FooTest extends TestCase {
                #[\\PHPUnit\\Framework\\Attributes\\RequiresPhp("^8.4")]
                #[\\PHPUnit\\Framework\\Attributes\\RequiresPhpunit("~11.0")]
                public function testFoo(): void {}
            }',
        ];

        yield 'attribute' => [
            '<?php class FooTest extends TestCase {
                #[
                    \\PHPUnit\\Framework\\Attributes\\RequiresPhp(\'>= 8.4\'),
                    \\PHPUnit\\Framework\\Attributes\\RequiresPhpunit(">= 11")
                ]
                public function testFoo(): void {}
            }',
            '<?php class FooTest extends TestCase {
                #[
                    \\PHPUnit\\Framework\\Attributes\\RequiresPhp(\'8.4\'),
                    \\PHPUnit\\Framework\\Attributes\\RequiresPhpunit("11")
                ]
                public function testFoo(): void {}
            }',
        ];

        yield 'attribute with float' => [
            '<?php class FooTest extends TestCase {
                #[
                    \\PHPUnit\\Framework\\Attributes\\RequiresPhp(8.4),
                    \\PHPUnit\\Framework\\Attributes\\RequiresPhpunit(">= 11")
                ]
                public function testFoo(): void {}
            }',
            '<?php class FooTest extends TestCase {
                #[
                    \\PHPUnit\\Framework\\Attributes\\RequiresPhp(8.4),
                    \\PHPUnit\\Framework\\Attributes\\RequiresPhpunit("11")
                ]
                public function testFoo(): void {}
            }',
        ];

        yield 'attribute with concatenation' => [
            '<?php class FooTest extends TestCase {
                #[
                    \\PHPUnit\\Framework\\Attributes\\RequiresPhp(">=" . "8.4"),
                    \\PHPUnit\\Framework\\Attributes\\RequiresPhpunit(">= 11")
                ]
                public function testFoo(): void {}
            }',
            '<?php class FooTest extends TestCase {
                #[
                    \\PHPUnit\\Framework\\Attributes\\RequiresPhp(">=" . "8.4"),
                    \\PHPUnit\\Framework\\Attributes\\RequiresPhpunit("11")
                ]
                public function testFoo(): void {}
            }',
        ];

        yield 'attributes' => [
            '<?php class FooTest extends TestCase {
                #[\\LeaveMeAlone("1.2")]
                #[\\PHPUnit\\Framework\\Attributes\\RequiresPhp(\'>= 8.4\')]
                #[\\PHPUnit\\Framework\\Attributes\\RequiresPhpunit(">= 11")]
                public function testFoo(): void {}
            }',
            '<?php class FooTest extends TestCase {
                #[\\LeaveMeAlone("1.2")]
                #[\\PHPUnit\\Framework\\Attributes\\RequiresPhp(\'8.4\')]
                #[\\PHPUnit\\Framework\\Attributes\\RequiresPhpunit("11")]
                public function testFoo(): void {}
            }',
        ];

        yield 'attributes with comparison constraints' => [
            '<?php class FooTest extends TestCase {
                #[\\LeaveMeAlone("1.2")]
                #[\\PHPUnit\\Framework\\Attributes\\RequiresPhp("!= 8.2")]
                #[\\PHPUnit\\Framework\\Attributes\\RequiresPhpunit("< 11")]
                public function testFoo(): void {}
            }',
            '<?php class FooTest extends TestCase {
                #[\\LeaveMeAlone("1.2")]
                #[\\PHPUnit\\Framework\\Attributes\\RequiresPhp("!=8.2")]
                #[\\PHPUnit\\Framework\\Attributes\\RequiresPhpunit("<11")]
                public function testFoo(): void {}
            }',
        ];
    }

    /**
     * @return iterable<string, array{0: string, 1?: string}>
     */
    private static function getFixCases(): iterable
    {
        yield 'no PHPDoc' => [''];

        yield 'single annotation without operator' => [
            '/**
              * @requires PHP >= 8.4
              */',
            '/**
              * @requires PHP 8.4
              */',
        ];

        yield 'single annotation without space' => [
            '/**
              * @requires PHP >= 8.4
              */',
            '/**
              * @requires PHP >=8.4
              */',
        ];

        yield 'annotation with trailing spaces' => [
            '/**
              * @requires PHP >= 8.4    ' . '
              */',
            '/**
              * @requires PHP 8.4    ' . '
              */',
        ];

        yield 'multiple annotations' => [
            '/**
              * @requires PHP > 7
              * @requires PHPUnit < 12
              */',
            '/**
              * @requires PHP >7
              * @requires PHPUnit <    12
              */',
        ];
    }
}
