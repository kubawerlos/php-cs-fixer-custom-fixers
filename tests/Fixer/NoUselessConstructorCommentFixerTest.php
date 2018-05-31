<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Comment\NoEmptyCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUselessConstructorCommentFixer
 */
final class NoUselessConstructorCommentFixerTest extends AbstractFixerTestCase
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
             * Destructor
             */
             ',
        ];
        yield [
            '<?php
            /**
             * Reconstructor
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
             * Constructor
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
             * constructor
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
             * Constructor.
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
             * Foo Constructor
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
             * FooBar Constructor
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
             * Foo\Bar Constructor
             */
             ',
        ];

        yield [
            '<?php
            /**
             *
             * This class has awesome Constructor
             */
             ',
            '<?php
            /**
             * Foo Constructor
             *
             * This class has awesome Constructor
             */
             ',
        ];

        yield [
            '<?php
            /**
             * This class has awesome Constructor.
             *
             */
             ',
            '<?php
            /**
             * This class has awesome Constructor.
             *
             * Foo Constructor
             */
             ',
        ];

        yield [
            '<?php
            /**
             * @author John Doe
             * This class has awesome Constructor
             */
             ',
            '<?php
            /**
             * @author John Doe
             * Foo Constructor.
             * This class has awesome Constructor
             */
             ',
        ];

        yield [
            '<?php
            /** @see example.com
             */
             ',
            '<?php
            /** Foo Constructor
             * @see example.com
             */
             ',
        ];

        yield [
            '<?php
            //
            // This class has awesome Constructor.
             ',
            '<?php
            // Foo Constructor
            // This class has awesome Constructor.
             ',
        ];

        yield [
            '<?php
            // Foo is constructor
             ',
        ];
    }
}
