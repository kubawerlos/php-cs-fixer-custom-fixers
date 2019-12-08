<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocTypesTrimFixer
 */
final class PhpdocTypesTrimFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
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
                /**
                 * @customAnnotation Foo | Bar ($x) Bar | Foo
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @param    Foo        $x
                 *
                 * @return        Bar
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @param Foo|Bar $x
                 */
             ',
            '<?php
                /**
                 * @param Foo | Bar $x
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @return Foo|Bar
                 */
             ',
            '<?php
                /**
                 * @return Foo | Bar
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @param ClassA|ClassB|ClassC|ClassD|ClassE|ClassF|ClassG|ClassH $x should be 0 | 1
                 */
             ',
            '<?php
                /**
                 * @param ClassA | ClassB | ClassC | ClassD | ClassE | ClassF | ClassG | ClassH $x should be 0 | 1
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @notParam Foo | Bar $x
                 * @param Foo|Bar $x
                 * @param Foo|Bar $y
                 *
                 * @notReturn Foo | Bar
                 * @return Foo|Bar
                 */
                 function fooBar($x, $y) {}
                 /**
                  * @return Baz
                  */
                 function baz() {}
             ',
            '<?php
                /**
                 * @notParam Foo | Bar $x
                 * @param Foo | Bar $x
                 * @param Foo|Bar $y
                 *
                 * @notReturn Foo | Bar
                 * @return Foo | Bar
                 */
                 function fooBar($x, $y) {}
                 /**
                  * @return Baz
                  */
                 function baz() {}
             ',
        ];

        yield [
            '<?php
                /**
                 * @return Foo|Bar Multiline
                 *                   description
                 */
             ',
            '<?php
                /**
                 * @return Foo | Bar Multiline
                 *                   description
                 */
             ',
        ];
    }
}
