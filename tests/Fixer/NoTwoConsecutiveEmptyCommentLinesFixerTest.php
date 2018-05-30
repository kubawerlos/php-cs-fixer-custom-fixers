<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Comment\NoTrailingWhitespaceInCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoTwoConsecutiveEmptyCommentLinesFixer
 */
final class NoTwoConsecutiveEmptyCommentLinesFixerTest extends AbstractFixerTestCase
{
    public function testPriority() : void
    {
        $this->assertLessThan((new NoTrailingWhitespaceInCommentFixer())->getPriority(), $this->fixer->getPriority());
        $this->assertLessThan((new PhpdocTrimFixer())->getPriority(), $this->fixer->getPriority());
    }

    /**
     * @param string      $expected
     * @param string|null $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null) : void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases() : \Iterator
    {
        yield [
            '<?php
/*
 * Foo
 *
 * Bar
 */
',
        ];

        yield [
            '<?php
/*
 * Foo
 *
 * Bar
 */
',
            '<?php
/*
 * Foo
 *
 *
 * Bar
 */
',
        ];

        yield [
            '<?php
/**
 * Foo
 *
 * Bar
 */
',
            '<?php
/**
 * Foo
 *
 *
 * Bar
 */
',
        ];

        yield [
            '<?php
/*
 * Foo
 *
 * Bar
 */
',
            '<?php
/*
 * Foo
 *
 *
 *
 *
 * Bar
 */
',
        ];

        yield [
            '<?php
/**
 * Foo
 *
 * Bar
 *
 * Baz
 */
',
            '<?php
/**
 * Foo
 *
 *
 *
 *
 * Bar
 *
 *
 *
 *
 *
 *
 *
 *
 * Baz
 */
',
        ];

        yield [
            '<?php
/**
 * Foo
 *
 */
',
            '<?php
/**
 * Foo
 *
 *
 *
 *
 */
',
        ];

        yield [
            '<?php
/*
 *
 * Foo
 */
',
            '<?php
/*
 *
 *
 *
 *
 * Foo
 */
',
        ];
    }
}
