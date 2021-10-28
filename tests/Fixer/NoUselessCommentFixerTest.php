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

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUselessCommentFixer
 */
final class NoUselessCommentFixerTest extends AbstractFixerTestCase
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
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFixCases(): iterable
    {
        yield ['<?php
            /**
             * Class Foo.
             */
             foo();
             '];

        yield ['<?php
            /**
             * DoNotRemoveMe
             */
            class Foo {}
            '];

        yield [
            '<?php
            /**
             */
            class Foo {}
            ',
            '<?php
            /**
             * Class Bar.
             */
            class Foo {}
            ',
        ];

        yield [
            '<?php
            /**
             */
            class Foo {}
            ',
            '<?php
            /**
             * Class Foo.
             */
            class Foo {}
            ',
        ];

        yield [
            '<?php
            /** */
            class Foo {}
            ',
            '<?php
            /** Class Foo */
            class Foo {}
            ',
        ];

        yield [
            '<?php
            /**
             */
            class Bar {}
            ',
            '<?php
            /**
             * Class Foo\Bar.
             */
            class Bar {}
            ',
        ];

        yield [
            '<?php
            /**
             */
            class Foo {}
            ',
            '<?php
            /**
             * Class Foo
             */
            class Foo {}
            ',
        ];

        yield [
            '<?php
            /**
             */
            class Foo {}
            ',
            '<?php
            /**
             * Foo
             */
            class Foo {}
            ',
        ];

        yield [
            '<?php
            /**
             */
            class Foo {}
            ',
            '<?php
            /**
             * Foo.
             */
            class Foo {}
            ',
        ];

        yield [
            '<?php
            /**
             */
            interface Foo {}
            ',
            '<?php
            /**
             * Interface Foo
             */
            interface Foo {}
            ',
        ];

        yield [
            '<?php
            /**
             */
            trait Foo {}
            ',
            '<?php
            /**
             * Trait Foo
             */
            trait Foo {}
            ',
        ];

        yield [
            '<?php
            /**
             *
             * Class provides nice functionality
             */
            class Foo {}
            ',
            '<?php
            /**
             * Class Foo.
             *
             * Class provides nice functionality
             */
            class Foo {}
            ',
        ];

        yield [
            '<?php
            /**
             * Class provides nice functionality
             *
             */
            class Foo {}
            ',
            '<?php
            /**
             * Class provides nice functionality
             *
             * Class Foo.
             */
            class Foo {}
            ',
        ];

        yield [
            '<?php
            /**
             * @author John Doe
             * Class is cool
             */
            class Foo {}
            ',
            '<?php
            /**
             * @author John Doe
             * Class Foo.
             * Class is cool
             */
            class Foo {}
            ',
        ];

        yield [
            '<?php
            /** ' . '
             * @see example.com
             */
            abstract class Foo {}
            ',
            '<?php
            /** Class Foo
             * @see example.com
             */
            abstract class Foo {}
            ',
        ];

        yield [
            '<?php
            //
            // Class that does something
            final class Foo {}
            ',
            '<?php
            // Class Foo
            // Class that does something
            final class Foo {}
            ',
        ];

        yield [
            '<?php
            #
            # Class that does something
            final class Foo {}
            ',
            '<?php
            # Class Foo
            # Class that does something
            final class Foo {}
            ',
        ];

        yield [
            '<?php
            // I am class Foo
            class Foo {}
            ',
        ];

        yield [
            '<?php
            // Class Foo
            if (true) {
                return false;
            }
            ',
        ];

        yield [
            '<?php
             /**
              * @coversDefaultClass CoveredClass
              */
             class Foo {}
            ',
        ];

        yield [
            '<?php
            /**
             * @coversDefaultClass ClassCovered
             */
            class Foo {}
            ',
        ];

        yield [
            '<?php
            class Foo {
                /**
                 */
                public function __constructor() {}
            }
            ',
            '<?php
            class Foo {
                /**
                 * Foo constructor.
                 */
                public function __constructor() {}
            }
            ',
        ];

        yield [
            '<?php
            class Foo {
                /**
                 */
                public function setA() {}
                /**
                 */
                public static function setB() {}
                /**
                 */
                static public function setC() {}
                /**
                 */
                protected function setD() {}
                /**
                 */
                private function setE() {}
                /**
                 */
                private function getF() {}
                /**
                 */
                private function setG() {}
                /**
                 */
                private function getH() {}
                /**
                 * Does not really gets I
                 */
                private function getI() {}
                /**
                 * Get J in a fancy way
                 */
                private function getJ() {}
            }
            ',
            '<?php
            class Foo {
                /**
                 * Set A
                 */
                public function setA() {}
                /**
                 * Set B.
                 */
                public static function setB() {}
                /**
                 * Set C
                 */
                static public function setC() {}
                /**
                 * Set D
                 */
                protected function setD() {}
                /**
                 * Sets E
                 */
                private function setE() {}
                /**
                 * Get F
                 */
                private function getF() {}
                /**
                 * Gets G
                 */
                private function setG() {}
                /**
                 * Gets H.
                 */
                private function getH() {}
                /**
                 * Does not really gets I
                 */
                private function getI() {}
                /**
                 * Get J in a fancy way
                 */
                private function getJ() {}
            }
            ',
        ];

        yield [
            '<?php
                class Foo {
                    /**
                     */
                    public function baz() {}
                }
            ',
            '<?php
                class Foo {
                    /**
                     * Add baz
                     * Adds baz
                     * Get baz
                     * Gets baz
                     * Remove baz
                     * Removes baz
                     * Set baz
                     * Sets baz
                     */
                    public function baz() {}
                }
            ',
        ];

        yield [
            '<?php
                //
                class Foo {
                    public function calculation() {
                        // @author: John Doe
                        return  42;
                    }
                }
            ',
            '<?php
                // Class Foo
                class Foo {
                    public function calculation() {
                        // @author: John Doe
                        return  42;
                    }
                }
            ',
        ];

        yield [
            '<?php
            /**
             */
            class Bar {}
            // foo
            ',
            '<?php
            /**
             * Class Foo\Bar.
             */
            class Bar {}
            // foo
            ',
        ];
    }
}
