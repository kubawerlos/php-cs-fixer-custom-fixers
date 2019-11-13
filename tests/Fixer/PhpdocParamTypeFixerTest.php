<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocParamTypeFixer
 */
final class PhpdocParamTypeFixerTest extends AbstractFixerTestCase
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

    public function provideFixCases(): iterable
    {
        yield [
            '<?php
            /**
             * @param mixed $foo
             */
             ',
        ];

        yield [
            '<?php
            /**
             * @param Foo
             */
             ',
        ];

        yield [
            '<?php
            /**
             * @param mixed $foo
             */
             ',
            '<?php
            /**
             * @param $foo
             */
             ',
        ];

        yield [
            '<?php
            /**
             * @param mixed $foo
             */
             ',
            '<?php
            /**
             * @param$foo
             */
             ',
        ];

        yield [
            '<?php
            /**
             * @param mixed                          $foo
             */
             ',
            '<?php
            /**
             * @param                                $foo
             */
             ',
        ];

        yield [
            '<?php
            /**
             * @param int $foo
             * @param mixed $bar
             */
             ',
            '<?php
            /**
             * @param int $foo
             * @param     $bar
             */
             ',
        ];

        yield [
            '<?php
            /**
             * @param string $foo
             * @param mixed  $bar
             */
             ',
            '<?php
            /**
             * @param string $foo
             * @param        $bar
             */
             ',
        ];

        yield [
            '<?php
            /**
             * @return $this
             */
             ',
        ];

        yield [
            '<?php
                /** first comment */
                /**
                 * @param bool $a
                 */
                function foo($a) {}
                /**
                 * @param mixed $a
                 */
                function bar($a) {}
',
            '<?php
                /** first comment */
                /**
                 * @param bool $a
                 */
                function foo($a) {}
                /**
                 * @param $a
                 */
                function bar($a) {}
',
        ];
    }
}
