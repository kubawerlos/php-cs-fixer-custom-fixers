<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Comment\NoEmptyCommentFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use PhpCsFixer\Fixer\Whitespace\NoTrailingWhitespaceFixer;
use PhpCsFixer\Fixer\Whitespace\NoWhitespaceInBlankLineFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocNoIncorrectVarAnnotationFixer
 */
final class PhpdocNoIncorrectVarAnnotationFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new NoExtraBlankLinesFixer())->getPriority(), $this->fixer->getPriority());
        static::assertGreaterThan((new NoEmptyCommentFixer())->getPriority(), $this->fixer->getPriority());
        static::assertGreaterThan((new NoEmptyPhpdocFixer())->getPriority(), $this->fixer->getPriority());
        static::assertGreaterThan((new NoTrailingWhitespaceFixer())->getPriority(), $this->fixer->getPriority());
        static::assertGreaterThan((new NoUnusedImportsFixer())->getPriority(), $this->fixer->getPriority());
        static::assertGreaterThan((new NoWhitespaceInBlankLineFixer())->getPriority(), $this->fixer->getPriority());
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
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): iterable
    {
        yield [
            '<?php
/** @var Foo $foo */
$foo = new Foo();
',
        ];

        yield [
            '<?php
/** @var \Foo $foo */
$foo = new Foo();
',
        ];

        yield [
            '<?php
/** @var ?Foo $foo */
$foo = new Foo();
',
        ];

        yield [
            '<?php
$bar = new Logger();
',
            '<?php
/** @var LoggerInterface $foo */
$bar = new Logger();
',
        ];

        yield [
            '<?php
$bar = new Logger();
',
            '<?php
/** @var $bar */
$bar = new Logger();
',
        ];

        yield [
            '<?php
/**
 * We create here new instance here
 */
$bar = new Logger();
',
            '<?php
/**
 * We create here new instance here
 * @var LoggerInterface $foo
 */
$bar = new Logger();
',
        ];

        yield [
            '<?php
return true;
',
            '<?php
/** @var bool $foo */
return true;
',
        ];

        yield [
            '<?php
',
            '<?php
/** @var LoggerInterface $foo */
',
        ];

        yield [
            '<?php
/** @see LoggerInterface $foo */
$bar = new Logger();
',
        ];

        yield [
            '<?php
/** @var LoggerInterface[] $foo */
foreach ($foo as $bar) {}
',
        ];

        yield [
            '<?php
/** @var LoggerInterface $bar */
foreach ($foo as $bar) {}
',
        ];

        yield [
            '<?php
/** @var LoggerInterface $bar */
$Bar = 2;
',
        ];

        yield [
            '<?php
class Foo
{
    /* @var int */
    static $a;

    /*
     * @var int
     */
    public $b;

    /** @var int */
    protected $c;

    /**
     * @var int
     */
    private $d;
}
',
        ];

        yield [
            '<?php
class Foo
{
    private $a;

    /**
     */
    private $b;

    /**      */
    private $c;

    /**
*/
    private $d;
}
',
            '<?php
class Foo
{
    /** @var */
    private $a;

    /**
     * @var
     */
    private $b;

    /** @var $foo
     */
    private $c;

    /**
      * @var $foo */
    private $d;
}
',
        ];
    }
}
