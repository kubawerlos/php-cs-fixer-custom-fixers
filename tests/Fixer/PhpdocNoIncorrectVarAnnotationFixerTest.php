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
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocNoIncorrectVarAnnotationFixer
 */
final class PhpdocNoIncorrectVarAnnotationFixerTest extends AbstractFixerTestCase
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

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFixCases(): iterable
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
for ($i = 0; $i < 100; $i++) {}
',
            '<?php
/** @var int $index */
for ($i = 0; $i < 100; $i++) {}
',
        ];

        yield [
            '<?php
/** @var int $i */
for ($i = 0; $i < 100; $i++) {}
',
        ];

        yield [
            '<?php
foreach ($foo as $v) {}
',
            '<?php
/** @var int $value */
foreach ($foo as $v) {}
',
        ];

        yield [
            '<?php
/** @var int $value */
foreach ($foo as $value) {}
',
        ];

        yield [
            '<?php
if (($v = getValue()) !== null) {}
',
            '<?php
/** @var int $value */
if (($v = getValue()) !== null) {}
',
        ];

        yield [
            '<?php
/** @var int $value */
if (($value = getValue()) !== null) {}
',
        ];

        yield [
            '<?php
switch ($v = getValue()) { default: break; }
',
            '<?php
/** @var int $value */
switch ($v = getValue()) { default: break; }
',
        ];

        yield [
            '<?php
/** @var int $value */
switch ($value = getValue()) { default: break; }
',
        ];

        yield [
            '<?php
while ($i < 0) { $i++; }
',
            '<?php
/** @var int $index */
while ($i < 0) { $i++; }
',
        ];

        yield [
            '<?php
/** @var int $index */
while ($index < 0) { $i++; }
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
    /** @var int */
    static $a;

    /**
     * @var int
     */
    public $b;

    /** @var int */
    protected $c;

    /**
     * @var int
     */
    private $d;

    /**
     * @var int
     */
    var $e;
}
',
        ];

        yield [
            '<?php
class Foo
{
    static $a;

    /**
     */
    public $b;

    /**
     */
    protected $c;

    /**
     */
    private $d;

    /**
      *
      */
    var $e;
}
',
            '<?php
class Foo
{
    /** @var */
    static $a;

    /**
     * @var
     */
    public $b;

    /** @var $foo
     */
    protected $c;

    /**
     * @var $foo */
    private $d;

    /**
      * @var $foo
      *
      */
    var $e;
}
',
        ];

        yield [
            '<?php
/** Class Foo */
class Foo
{
    private $a;

    public function hello()
    {
        foreach ($b as $x) {}

        $b = [1, 2, 3];

        foreach ($b as $x) {}
    }
}
',
            '<?php
/** Class Foo */
class Foo
{
    /** @var $b */
    private $a;

    public function hello()
    {
        /** @var $y */
        foreach ($b as $x) {}

        /** @var $a */
        $b = [1, 2, 3];

        /** @var $y */
        foreach ($b as $x) {}
    }
}
',
        ];
    }
}
