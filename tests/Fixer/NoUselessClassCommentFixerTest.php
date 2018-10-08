<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Comment\NoEmptyCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUselessClassCommentFixer
 */
final class NoUselessClassCommentFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new NoEmptyPhpdocFixer())->getPriority(), $this->fixer->getPriority());
        static::assertGreaterThan((new NoEmptyCommentFixer())->getPriority(), $this->fixer->getPriority());
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
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): \Generator
    {
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
            class Foo {}
             ',
            '<?php
            /**
             * Class Foo\Bar.
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
             * Class Foo
             */
            class Foo {}
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
    }
}
