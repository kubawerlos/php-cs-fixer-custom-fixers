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
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocNoSuperfluousParamFixer
 */
final class PhpdocNoSuperfluousParamFixerTest extends AbstractFixerTestCase
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
        yield ['<?php
/**
 * @param bool $b
 * @param int $i
 * @param string $s
 */
function foo($b, $i, $s) {}
'];

        yield ['<?php
/**
 * @param foo &$a
 * @param foo ...$b
 */
function bar(&$a, ...$b) {}
'];

        yield ['<?php
/**
 * @param foo& $a
 * @param foo... $b
 */
function bar(&$a, ...$b) {}
'];

        yield ['<?php
/**
 * @param foo & $a
 * @param foo ... $b
 */
function bar(&$a, ...$b) {}
'];

        yield ['<?php
function foo($b) {}
/**
 * @param bool $b
 * @param int $i
 */
'];

        yield [
            '<?php
/**
 * @param bool $b
 * @param string $s
 */
function foo($b, $s) {}
',
            '<?php
/**
 * @param bool $b
 * @param int $i
 * @param string $s
 */
function foo($b, $s) {}
',
        ];

        yield [
            '<?php
/**
 * @param $x
 */
function foo($x) {}
',
            '<?php
/**
 * @param $x
 * @param $y
 */
function foo($x) {}
',
        ];

        yield [
            '<?php
/**
 */
function foo() {}
',
            '<?php
/**
 * @param int $x
 */
function foo() {}
',
        ];

        yield [
            '<?php
/**
 * @param $x
 */
/* private */ function foo($x) {}
',
            '<?php
/**
 * @param $x
 * @param $y
 */
/* private */ function foo($x) {}
',
        ];

        yield [
            '<?php
/**
 * @param Type $x First one
 */
function foo($x) {}
',
            '<?php
/**
 * @param Type $x First one
 * @param Type $x Second one one
 */
function foo($x) {}
',
        ];

        yield [
            '<?php
/**
 * @param bool $b
 */
function foo($b) {}
',
            '<?php
/**
 * @param bool $b
 * @param int $x This is not $b
 */
function foo($b) {}
',
        ];

        yield [
            '<?php
/**
 * @param array<int, int> $b
 */
function foo($b) {}
',
            '<?php
/**
 * @param array<int, int> $a
 * @param array<int, int> $b
 * @param array<int, int> $c
 */
function foo($b) {}
',
        ];

        yield [
            '<?php

/**
 */

function foo() {}
',
            '<?php

/**
 * @param $a
 */

function foo() {}
',
        ];

        yield [
            '<?php



function foo() {}
',
            '<?php

/** @param $a */

function foo() {}
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
 * @param bool $b
 */
function bar($a) {}
',
        ];

        foreach (['abstract', 'final', 'private', 'protected', 'public', 'static', '/* private */'] as $modifier) {
            yield [
                \sprintf('<?php
                    abstract class Foo {
                        /**
                         * @param $a
                         */
                        %s function bar($a) %s
                    }
                ', $modifier, $modifier === 'abstract' ? ';' : '{}'),
                \sprintf('<?php
                    abstract class Foo {
                        /**
                         * @param $a
                         * @param $b
                         */
                        %s function bar($a) %s
                    }
                ', $modifier, $modifier === 'abstract' ? ';' : '{}'),
            ];
        }
    }
}
