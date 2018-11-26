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
            class Foo {
                /**
                 * Destructor
                 */
                 public function __constructor() {}
             }
             ',
        ];
        yield [
            '<?php
            class Foo {
                /**
                 * Reconstructor
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
                 public function __constructor() {}
             }
             ',
            '<?php
            class Foo {
                /**
                 * Constructor
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
                 public function __constructor() {}
             }
             ',
            '<?php
            class Foo {
                /**
                 * constructor
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
                 public function __constructor() {}
             }
             ',
            '<?php
            class Foo {
                /**
                 * Constructor.
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
                 public function __constructor() {}
             }
             ',
            '<?php
            class Foo {
                /**
                 * Foo Constructor
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
                 public function __constructor() {}
             }
             ',
            '<?php
            class Foo {
                /**
                 * FooBar Constructor
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
                 public function __constructor() {}
             }
             ',
            '<?php
            class Foo {
                /**
                 * Foo\Bar Constructor
                 */
                 public function __constructor() {}
             }
             ',
        ];

        yield [
            '<?php
            class Foo {
                /**
                 *
                 * This class has awesome Constructor
                 */
                 public function __constructor() {}
             }
             ',
            '<?php
            class Foo {
                /**
                 * Foo Constructor
                 *
                 * This class has awesome Constructor
                 */
                 public function __constructor() {}
             }
             ',
        ];

        yield [
            '<?php
            class Foo {
                /**
                 * This class has awesome Constructor.
                 *
                 */
                 public function __constructor() {}
             }
             ',
            '<?php
            class Foo {
                /**
                 * This class has awesome Constructor.
                 *
                 * Foo Constructor
                 */
                 public function __constructor() {}
             }
             ',
        ];

        yield [
            '<?php
            class Foo {
                /**
                 * @author John Doe
                 * This class has awesome Constructor
                 */
                 public function __constructor() {}
             }
             ',
            '<?php
            class Foo {
                /**
                 * @author John Doe
                 * Foo Constructor.
                 * This class has awesome Constructor
                 */
                 public function __constructor() {}
             }
             ',
        ];

        yield [
            '<?php
            class Foo {
                /** @see example.com
                 */
                 public function __constructor() {}
             }
             ',
            '<?php
            class Foo {
                /** Foo Constructor
                 * @see example.com
                 */
                 public function __constructor() {}
             }
             ',
        ];

        yield [
            '<?php
            class Foo {
                //
                // This class has awesome Constructor.
                 public function __constructor() {}
             }
             ',
            '<?php
            class Foo {
                // Foo Constructor
                // This class has awesome Constructor.
                 public function __constructor() {}
             }
             ',
        ];

        yield [
            '<?php
            class Foo {
                // Foo is constructor
                 public function __constructor() {}
             }
             ',
        ];
    }
}
