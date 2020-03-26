<?php

declare(strict_types=1);

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
                /**
                 * @param mixed $a
                 */
                function foo($a) {}
                /**
                 * @param bool $a
                 */
                function bar($a) {}
                /** comment */
',
            '<?php
                /**
                 * @param $a
                 */
                function foo($a) {}
                /**
                 * @param bool $a
                 */
                function bar($a) {}
                /** comment */
',
        ];
    }
}
