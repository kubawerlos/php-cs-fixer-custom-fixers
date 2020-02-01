<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUselessConstructorCommentFixer
 */
final class NoUselessConstructorCommentFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertFalse($this->fixer->isRisky());
    }

    public function testSuccessorName(): void
    {
        self::assertContains('NoUselessCommentFixer', $this->fixer->getSuccessorsNames());
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public static function provideFixCases(): iterable
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
                /**
                 * @see example.com
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
