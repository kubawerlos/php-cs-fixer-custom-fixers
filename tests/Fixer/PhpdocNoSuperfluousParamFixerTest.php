<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocNoSuperfluousParamFixer
 */
final class PhpdocNoSuperfluousParamFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new NoEmptyPhpdocFixer())->getPriority(), $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): \Iterator
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
 * @param foo& $b
 * @param foo & $c
 * @param foo ...$d
 * @param foo... $e
 * @param foo ... $f
 */
function bar(&$a, &$b, &$c, ...$d, ...$e, ...$f) {}
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
    }
}
