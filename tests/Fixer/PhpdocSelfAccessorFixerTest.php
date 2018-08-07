<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocSelfAccessorFixer
 */
final class PhpdocSelfAccessorFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertSame(0, $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    /**
     * @param string      $expected
     * @param string|null $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): \Iterator
    {
        yield [
            '<?php
class Foo {
    /**
     * @var FooBar
     */
     private $instance;
     
     /**
      * @param Foo\Bar $bar
      *
      * @return Bar\Foo
      */
      public function bar() {}
}',
        ];

        yield [
            '<?php
class Foo {
    /**
     * @var self
     */
     private $instance;
     
     /**
      * @param self $foo
      *
      * @return self
      */
      public function bar() {}
}',
            '<?php
class Foo {
    /**
     * @var Foo
     */
     private $instance;
     
     /**
      * @param Foo $foo
      *
      * @return Foo
      */
      public function bar() {}
}',
        ];
    }
}
