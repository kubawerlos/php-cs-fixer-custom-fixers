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
    public function testPriority() : void
    {
        $this->assertGreaterThan((new NoEmptyPhpdocFixer())->getPriority(), $this->fixer->getPriority());
        $this->assertGreaterThan((new NoEmptyCommentFixer())->getPriority(), $this->fixer->getPriority());
        $this->assertGreaterThan((new PhpdocTrimFixer())->getPriority(), $this->fixer->getPriority());
    }

    /**
     * @param string      $expected
     * @param string|null $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null) : void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases() : \Iterator
    {
        yield [
            '<?php
            /**
             */
             ',
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
             ',
            '<?php
            /**
             * Class Foo\Bar.
             */
             ',
        ];

        yield [
            '<?php
            /**
             */
             ',
            '<?php
            /**
             * Class Foo
             */
             ',
        ];

        yield [
            '<?php
            /**
             *
             * Class provides nice functionality
             */
             ',
            '<?php
            /**
             * Class Foo.
             *
             * Class provides nice functionality
             */
             ',
        ];

        yield [
            '<?php
            /**
             * Class provides nice functionality
             *
             */
             ',
            '<?php
            /**
             * Class provides nice functionality
             *
             * Class Foo.
             */
             ',
        ];

        yield [
            '<?php
            /**
             * @author John Doe
             * Class is cool
             */
             ',
            '<?php
            /**
             * @author John Doe
             * Class Foo.
             * Class is cool
             */
             ',
        ];

        yield [
            '<?php
            /** @see example.com
             */
             ',
            '<?php
            /** Class Foo
             * @see example.com
             */
             ',
        ];

        yield [
            '<?php
            //
            // Class that does something
             ',
            '<?php
            // Class Foo
            // Class that does something
             ',
        ];

        yield [
            '<?php
            // I am class Foo
             ',
        ];
    }
}
