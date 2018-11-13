<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Comment\NoEmptyCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimConsecutiveBlankLineSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUselessCommentFixer
 */
final class NoUselessCommentFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new NoEmptyCommentFixer())->getPriority(), $this->fixer->getPriority());
        static::assertGreaterThan((new NoEmptyPhpdocFixer())->getPriority(), $this->fixer->getPriority());
        static::assertGreaterThan((new PhpdocTrimConsecutiveBlankLineSeparationFixer())->getPriority(), $this->fixer->getPriority());
        static::assertGreaterThan((new PhpdocTrimFixer())->getPriority(), $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideTestCases
     */
    public function testFix(string $expected, string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideTestCases(): \Generator
    {
        yield [
            '<?php
            /**
             * Class Foo.
             */
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
            /** @see example.com
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
    }
}
