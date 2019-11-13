<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocParamOrderFixer
 */
final class PhpdocParamOrderFixerTest extends AbstractFixerTestCase
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
                 * @param bool $b
                 * @param int $i
                 * @param string $s
                 */
                function foo($b, $i, $s) {}
            ',
        ];

        yield [
            '<?php
                function foo($a, $b) {}
                /**
                 * @param int $b
                 * @param int $a
                 */
            ',
        ];

        foreach (['abstract', 'final', 'private', 'protected', 'public', 'static', '/* private */'] as $modifier) {
            yield [
                \sprintf('<?php
                    class Foo {
                        /**
                         * @param bool $b
                         * @param int $i
                         * @param string $s
                         */
                        %s function bar($b, $i, $s) {}
                    }
                ', $modifier),
                \sprintf('<?php
                    class Foo {
                        /**
                         * @param bool $b
                         * @param string $s
                         * @param int $i
                         */
                        %s function bar($b, $i, $s) {}
                    }
                ', $modifier),
            ];
        }

        yield [
            '<?php
                /**
                 * @param bool $b
                 * @param int $i
                 * @param string $s
                 */
                function foo(bool $b, ?int $i, string $s = null) {}
            ',
            '<?php
                /**
                 * @param bool $b
                 * @param string $s
                 * @param int $i
                 */
                function foo(bool $b, ?int $i, string $s = null) {}
            ',
        ];

        yield [
            '<?php
                /**
                 * @param $a
                 * @param $c
                 */
                function foo($a, int $b, $c) {}
            ',
            '<?php
                /**
                 * @param $c
                 * @param $a
                 */
                function foo($a, int $b, $c) {}
            ',
        ];

        yield [
            '<?php
                /**
                 * @param $a Description
                 *           of $a
                 * @param $b Description
                 *           of $b
                 */
                function foo($a, $b) {}
            ',
            '<?php
                /**
                 * @param $b Description
                 *           of $b
                 * @param $a Description
                 *           of $a
                 */
                function foo($a, $b) {}
            ',
        ];

        yield [
            '<?php
                /**
                 * @see www.example.com
                 * @param int $a
                 * @param int $b
                 * @param int $c
                 * @param int $begin
                 * @param int $aaa
                 * @param int $end
                 *
                 * @return int
                 */
                function foo($a, $b, $c) {}
            ',
            '<?php
                /**
                 * @see www.example.com
                 * @param int $begin
                 * @param int $a
                 * @param int $aaa
                 * @param int $b
                 * @param int $c
                 * @param int $end
                 *
                 * @return int
                 */
                function foo($a, $b, $c) {}
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
                 * @param bool $a
                 * @param bool $b
                 */
                function bar($a, $b) {}
            ',
            '<?php
                /** first comment */
                /**
                 * @param bool $a
                 */
                function foo($a) {}
                /**
                 * @param bool $b
                 * @param bool $a
                 */
                function bar($a, $b) {}
            ',
        ];

        yield [
            '<?php
                /**
                 * @author John Doe
                 *
                 * @param bool $a
                 * @param bool $b
                 */
                function foo($a, $b) {}
            ',
            '<?php
                /**
                 * @author John Doe
                 *
                 * @param bool $b
                 * @param bool $a
                 */
                function foo($a, $b) {}
            ',
        ];

        yield [
            '<?php
                /**
                 * @param bool $a
                 * @param bool $b
                 *
                 * @see example.com
                 */
                function foo($a, $b) {}
            ',
            '<?php
                /**
                 * @param bool $b
                 * @param bool $a
                 *
                 * @see example.com
                 */
                function foo($a, $b) {}
            ',
        ];

        yield [
            '<?php
                /**
                 * @author John Doe
                 *
                 * @param bool $a
                 * @param bool $b
                 *
                 * @see example.com
                 */
                function foo($a, $b) {}
            ',
            '<?php
                /**
                 * @author John Doe
                 *
                 * @param bool $b
                 * @param bool $a
                 *
                 * @see example.com
                 */
                function foo($a, $b) {}
            ',
        ];

        yield [
            '<?php
                /**
                 * @author John Doe
                 * @param bool $a
                 * @param bool $b
                 * @version 2.0
                 * @see example.com
                 */
                function foo($a, $b) {}
            ',
            '<?php
                /**
                 * @author John Doe
                 * @param bool $b
                 * @version 2.0
                 * @param bool $a
                 * @see example.com
                 */
                function foo($a, $b) {}
            ',
        ];
    }
}
