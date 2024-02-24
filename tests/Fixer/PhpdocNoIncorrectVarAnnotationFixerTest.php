<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

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
        self::assertRiskiness(false);
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
        yield 'keep correct PHPDoc' => ['<?php
/** @var Foo $foo */
$foo = new Foo();
'];

        yield 'keep correct PHPDoc with leading slash' => [
            '<?php
/** @var \Foo $foo */
$foo = new Foo();
'];

        yield 'keep correct PHPDoc with nullable' => [
            '<?php
/** @var ?Foo $foo */
$foo = new Foo();
'];

        yield 'remove PHPDoc when variable name is different' => [
            '<?php
$bar = new Logger();
',
            '<?php
/** @var LoggerInterface $foo */
$bar = new Logger();
',
        ];

        yield 'remove PHPDoc when annotation hs no type' => [
            '<?php
$bar = new Logger();
',
            '<?php
/** @var $bar */
$bar = new Logger();
',
        ];

        yield 'remove PHPDoc when variable name is different in for loop' => [
            '<?php
for ($i = 0; $i < 100; $i++) {}
',
            '<?php
/** @var int $index */
for ($i = 0; $i < 100; $i++) {}
',
        ];

        yield 'keep correct PHPDoc for for loop' => ['<?php
/** @var int $i */
for ($i = 0; $i < 100; $i++) {}
'];

        yield 'remove PHPDoc when variable name is different in foreach loop' => [
            '<?php
foreach ($foo as $v) {}
',
            '<?php
/** @var int $value */
foreach ($foo as $v) {}
',
        ];

        yield 'keep correct PHPDoc for array in foreach loop' => ['<?php
/** @var int[] $foo */
foreach ($foo as $bar) {}
',
        ];
        yield 'keep correct PHPDoc for element in foreach loop' => ['<?php
/** @var int $value */
foreach ($foo as $value) {}
'];

        yield 'remove PHPDoc when variable name is different in if condition' => [
            '<?php
if (($v = getValue()) !== null) {}
',
            '<?php
/** @var int $value */
if (($v = getValue()) !== null) {}
',
        ];

        yield 'keep correct PHPDoc for if condition' => ['<?php
/** @var int $value */
if (($value = getValue()) !== null) {}
'];

        yield 'remove PHPDoc when variable name is different in switch condition' => [
            '<?php
switch ($v = getValue()) { default: break; }
',
            '<?php
/** @var int $value */
switch ($v = getValue()) { default: break; }
',
        ];

        yield 'keep correct PHPDoc for switch condition' => ['<?php
/** @var int $value */
switch ($value = getValue()) { default: break; }
'];

        yield 'remove PHPDoc when variable name is different in while loop' => [
            '<?php
while ($i < 0) { $i++; }
',
            '<?php
/** @var int $index */
while ($i < 0) { $i++; }
',
        ];

        yield 'keep correct PHPDoc for while loop' => ['<?php
/** @var int $index */
while ($index < 0) { $i++; }
',
        ];

        yield 'remove PHPDoc when variable name is different, but keep the rest of PHPDoc' => [
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

        yield 'remove PHPDoc from before return' => [
            '<?php
return true;
',
            '<?php
/** @var bool $foo */
return true;
',
        ];

        yield 'remove PHPDoc from end of file' => [
            '<?php
',
            '<?php
/** @var LoggerInterface $foo */
',
        ];

        yield 'ignore other annotations' => ['<?php
/** @see LoggerInterface $foo */
$bar = new Logger();
'];

        yield 'ignore different variable name case' => ['<?php
/** @var LoggerInterface $bar */
$Bar = 2;
'];

        yield 'keep correct PHPDoc for class properties' => [
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

    /**
	 * Description of $f
	 * @var int
	 */
	private $f;

    /**
     * @var int
     */
    private static $g;
}
',
        ];

        if (\PHP_VERSION_ID >= 70400) {
            yield 'keep correct PHPDoc for class properties, PHP 7.4' => [
                '<?php class Foo
                {
                    /** @var array */
                    private array $array;

                    /** @var bool */
                    private bool $boolean;

                    /** @var null|string */
                    private ?string $nullableString;

                    /** @var Bar */
                    private Bar $bar;

                    /** @var Vendor\Baz */
                    private Vendor\Baz $baz;
                }',
            ];
        }

        yield 'remove PHPDoc for class properties' => [
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

        yield 'remove PHPDoc from inside of function' => [
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

        yield 'incorrect PHPDoc at the end of the file' => [
            '<?php
$x = 0;
',
            '<?php
/** @var this is incorrect */
$x = 0;
/** @var this is incorrect */
',
        ];

        yield 'remove PHPDoc from wrong places' => [
            '<?php class Foo {
                public function f1($x) {
                }
                public function f2($x) {
                    return $x;
                }
            }',
            '<?php class Foo {
                /** @var int */
                public function f1($x) {
                    /** @var int $x */
                }
                /** @var int $x */
                public function f2($x) {
                    /** @var int $x */
                    return $x;
                    /** @var int $x */
                }
                /** @var int */
            }',
        ];

        yield 'remove PHPDoc for class properties when variable names are different' => [
            '<?php class Foo {
                static $a;
                public $b;
                protected $c;
                private $d;
                var $e;
                private static $f;
            }',
            '<?php class Foo {
                /** @var int $x */
                static $a;
                /** @var int $x */
                public $b;
                /** @var int $x */
                protected $c;
                /** @var int $x */
                private $d;
                /** @var int $x */
                var $e;
                /** @var int $x */
                private static $f;
            }',
        ];

        yield 'remove PHPDoc for constants' => [
            '<?php class Foo {
                const A = 1;
                public const B = 2;
                protected const C = 3;
                private const D = 4;
            }',
            '<?php class Foo {
                /** @var int */
                const A = 1;
                /** @var int */
                public const B = 2;
                /** @var int */
                protected const C = 3;
                /** @var int */
                private const D = 4;
            }',
        ];
    }

    /**
     * @dataProvider provideFix80Cases
     *
     * @requires PHP ^8.0
     */
    public function testFix80(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFix80Cases(): iterable
    {
        yield 'keep correct PHPDoc for class properties, PHP 8.0' => [
            '<?php class Foo
                {
                    /** @var int|string */
                    private int|string $intOrString;
                }',
        ];

        yield 'keep correct PHPDoc for promoted properties, PHP 8.0' => [
            '<?php class Foo
                {
                    public function __construct(
                        /** @var array<Foo> */
                        public array $a,
                        /** @var array<Foo> */
                        public array $b,
                        /** @var array<Foo> */
                        protected array $c,
                        /** @var array<Foo> */
                        private array $d,
                    ) {}
                }',
        ];
    }

    /**
     * @dataProvider provideFix81Cases
     *
     * @requires PHP ^8.1
     */
    public function testFix81(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFix81Cases(): iterable
    {
        yield 'keep correct PHPDoc for class properties, PHP 8.1' => [
            '<?php class Foo
                {
                    /** @var string */
                    private readonly string $readonlyString;

                    /** @var Bar&Vendor\Baz */
                    private Bar&Vendor\Baz $barAndBaz;
                }',
        ];
    }
}
