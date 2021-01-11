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
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocSelfAccessorFixer
 */
final class PhpdocSelfAccessorFixerTest extends AbstractFixerTestCase
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

    public static function provideFixCases(): iterable
    {
        yield [ // no namespace - do not change
            '<?php
class Foo {
    /**
     * @var FooBar
     */
     private $instance;

     /**
      * @param Foo\Bar $x
      * @param Bar\Foo $x
      * @param int $x count of Foo
      * @see Foo documentation
      */
      public function bar(...$params) {}
}',
        ];

        yield [ // no namespace - change
            '<?php
class Foo {
    /**
     * @var self
     */
     private $instance;

     /**
      * @param self $x
      * @param self $x
      * @param bool|self $x
      * @param self|int $x
      * @param bool|self|int $x
      * @param bool|self|int $x
      * @param self[] $foos
      * @param self[] $foos
      * @param Array<int, self> $foos
      * @param Array<self, int> $foos
      *
      * @return self
      */
      public function bar(...$params) {}
}',
            '<?php
class Foo {
    /**
     * @var Foo
     */
     private $instance;

     /**
      * @param Foo $x
      * @param \Foo $x
      * @param bool|Foo $x
      * @param Foo|int $x
      * @param bool|Foo|int $x
      * @param bool|\Foo|int $x
      * @param Foo[] $foos
      * @param \Foo[] $foos
      * @param Array<int, Foo> $foos
      * @param Array<\Foo, int> $foos
      *
      * @return Foo
      */
      public function bar(...$params) {}
}',
        ];

        yield [ // with namespace - do not change
            '<?php
namespace Some\Thing;
class Foo {
     /**
      * @param \Foo $x
      * @param Some\Foo $x
      * @param Thing\Foo $x
      * @param Some\Thing\Foo $x
      * @param Foo\Some $x
      * @param Foo\Some\Thing $x
      * @param Foo\Some\Thing\Foo $x
      * @param \Foo[] $foos
      * @param Array<int, \Foo> $foos
      */
      public function bar(...$params) {}
}',
        ];

        yield [ // with namespace - change
            '<?php
namespace Some\Thing;
class Foo {
     /**
      * @param self $x
      * @param self $x
      * @param bool|self $x
      * @param self|int $x
      * @param bool|self|int $x
      * @param self[] $foos
      * @param self[] $foos
      * @param Array<int, self> $foos
      * @param Array<self, int> $foos
      */
      public function bar(...$params) {}
}',
            '<?php
namespace Some\Thing;
class Foo {
     /**
      * @param Foo $x
      * @param \Some\Thing\Foo $x
      * @param bool|\Some\Thing\Foo $x
      * @param \Some\Thing\Foo|int $x
      * @param bool|\Some\Thing\Foo|int $x
      * @param Foo[] $foos
      * @param \Some\Thing\Foo[] $foos
      * @param Array<int, Foo> $foos
      * @param Array<\Some\Thing\Foo, int> $foos
      */
      public function bar(...$params) {}
}',
        ];

        yield [
            '<?php
namespace Some\Thing;
class Foo {
     /**
      * @author Jon Doe
      * @param self $x
      * @param self $x
      */
      public function bar(...$params) {}
}',
            '<?php
namespace Some\Thing;
class Foo {
     /**
      * @author Jon Doe
      * @param self $x
      * @param Foo $x
      */
      public function bar(...$params) {}
}',
        ];

        yield [
            '<?php
namespace Some\Thing;
interface Foo {
     /**
      * @return Bar
      */
      public function init();
     /**
      * @return self
      */
      public function getInstance();
}',
            '<?php
namespace Some\Thing;
interface Foo {
     /**
      * @return Bar
      */
      public function init();
     /**
      * @return Foo
      */
      public function getInstance();
}',
        ];

        yield [
            '<?php
                namespace Custom\Error;
                class ReadingError {
                     /**
                      * @return self
                      */
                      public static function create() {}
                }
            ',
            '<?php
                namespace Custom\Error;
                class ReadingError {
                     /**
                      * @return \Custom\Error\ReadingError
                      */
                      public static function create() {}
                }
            ',
        ];
    }
}
