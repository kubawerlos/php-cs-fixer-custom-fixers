<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoCommentedOutCodeFixer
 */
final class NoCommentedOutCodeFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new NoUnusedImportsFixer())->getPriority(), $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): \Generator
    {
        yield ['<?php //'];
        yield ['<?php // do not remove me'];
        yield ['<?php # do not remove me'];
        yield ['<?php /* do not remove me */'];
        yield ['<?php /** do not remove me */'];
        yield ['<?php
                    /**
                     * do not remove me
                     */',
        ];

        yield ['<?php // var_dump("no semicolon after")'];

        yield [
            "<?php\n",
            "<?php\n// var_dump('x');",
        ];

        yield [
            "<?php\n",
            "<?php\n# var_dump('x');",
        ];

        yield [
            "<?php\n",
            "<?php\n/* var_dump('x'); */",
        ];

        yield [
            "<?php\n",
            "<?php\n/** var_dump('x'); */",
        ];

        yield [
            '<?php
              ',
            '<?php
                  // if (true) {
                  //     return 42;
                  // }
              ',
        ];

        yield [
            '<?php
                  // This line is a comment
              ',
            '<?php
                  // if (true) {
                  //     return 42;
                  // }
                  // This line is a comment
                  // if (false) {
                  //     return 100;
                  // }
              ',
        ];

        yield [
            '<?php
              ',
            '<?php
                  // /**
                  //  * remove me
                  //  */
              ',
        ];
    }
}
