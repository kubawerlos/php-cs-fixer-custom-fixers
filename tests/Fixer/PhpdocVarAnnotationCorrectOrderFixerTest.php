<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixerCustomFixers\Fixer\PhpdocNoIncorrectVarAnnotationFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocVarAnnotationCorrectOrderFixer
 */
final class PhpdocVarAnnotationCorrectOrderFixerTest extends AbstractFixerTestCase
{
    public function testPriority() : void
    {
        static::assertGreaterThan((new PhpdocNoIncorrectVarAnnotationFixer())->getPriority(), $this->fixer->getPriority());
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
/** @param $foo Foo */
',
        ];

        yield [
            '<?php
/** @var Foo $foo */
',
        ];

        yield [
            '<?php
/**
 * @var Foo $foo
 * @var Bar $bar
 */
',
            '<?php
/**
 * @var $foo Foo
 * @var $bar Bar
 */
',
        ];

        yield [
            '<?php
/** @var Foo $foo */
',
            '<?php
/** @var $foo Foo */
',
        ];

        yield [
            '<?php
/**@var Foo $foo*/
',
            '<?php
/**@var $foo Foo*/
',
        ];

        yield [
            '<?php
/** @Var Foo $foo */
',
            '<?php
/** @Var $foo Foo */
',
        ];

        yield [
            '<?php
/** @var Foo|Bar|mixed|int $someWeirdLongNAME__123 */
',
            '<?php
/** @var $someWeirdLongNAME__123 Foo|Bar|mixed|int */
',
        ];
    }
}
