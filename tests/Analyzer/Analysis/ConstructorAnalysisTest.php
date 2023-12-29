<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Analyzer\Analysis;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ConstructorAnalysis;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\Analysis\ConstructorAnalysis
 */
final class ConstructorAnalysisTest extends TestCase
{
    /**
     * @param array<string> $expected
     *
     * @dataProvider provideGettingConstructorParameterNamesCases
     */
    public function testGettingConstructorParameterNames(array $expected, string $code): void
    {
        $tokens = Tokens::fromCode($code);
        $analysis = new ConstructorAnalysis($tokens, 11);

        self::assertSame(11, $analysis->getConstructorIndex());
        self::assertSame($expected, $analysis->getConstructorParameterNames());
    }

    /**
     * @return iterable<array{array<string>, string}>
     */
    public static function provideGettingConstructorParameterNamesCases(): iterable
    {
        yield 'parameters without types' => [
            ['$a', '$b', '$c'],
            '<?php class Foo {
                public function __construct($a, $b, $c) {}
            }',
        ];

        yield 'parameters with types' => [
            ['$b', '$c', '$i', '$s'],
            '<?php class Foo {
                public function __construct(bool $b, callable $c, int $i, string $s) {}
            }',
        ];

        yield 'parameters with defaults' => [
            ['$x', '$y', '$z'],
            '<?php class Foo {
                public function __construct(Foo $x = null, ?Bar $y = null, float $z = 3.14) {}
            }',
        ];
    }

    /**
     * @param array<string> $expected
     *
     * @dataProvider provideGettingConstructorParameterNames80Cases
     *
     * @requires PHP ^8.0
     */
    public function testGettingConstructorParameterNames80(array $expected, string $code): void
    {
        $tokens = Tokens::fromCode($code);
        $analysis = new ConstructorAnalysis($tokens, 11);

        self::assertSame(11, $analysis->getConstructorIndex());
        self::assertSame($expected, $analysis->getConstructorParameterNames());
    }

    /**
     * @return iterable<array{array<string>, string}>
     */
    public static function provideGettingConstructorParameterNames80Cases(): iterable
    {
        yield 'some already promoted' => [
            ['$a', '$b', '$q', '$s', '$t'],
            '<?php class Foo {
                    public function __construct(public array $a, bool $b, protected ?Bar\Baz\Qux $q, string $s, private OtherType $t) {}
                }',
        ];
    }

    /**
     * @param array<int, string> $expected
     *
     * @dataProvider provideGettingConstructorPromotableParametersCases
     */
    public function testGettingConstructorPromotableParameters(array $expected, string $code): void
    {
        $tokens = Tokens::fromCode($code);
        $analysis = new ConstructorAnalysis($tokens, 11);

        self::assertSame(11, $analysis->getConstructorIndex());
        self::assertSame($expected, $analysis->getConstructorPromotableParameters());
    }

    /**
     * @return iterable<array{array<int, string>, string}>
     */
    public static function provideGettingConstructorPromotableParametersCases(): iterable
    {
        yield 'simple parameters' => [
            [15 => '$a', 20 => '$b', 25 => '$i'],
            '<?php class Foo {
                public function __construct(array $a, bool $b, int $i) {}
            }',
        ];

        yield 'parameters without types are not supported' => [
            [21 => '$i'],
            '<?php class Foo {
                public function __construct($noType1, $noType2, int $i, $noType3) {}
            }',
        ];

        yield 'callable is not promotable' => [
            [15 => '$a', 20 => '$b', 35 => '$i'],
            '<?php class Foo {
                public function __construct(array $a, bool $b, callable $c1, CALLABLE $c1, int $i) {}
            }',
        ];

        yield 'variadic parameter is not promotable' => [
            [15 => '$x', 20 => '$y'],
            '<?php class Foo {
                public function __construct(int $x, int $y, int ...$z) {}
            }',
        ];
    }

    /**
     * @param array<int, string> $expected
     *
     * @dataProvider provideGettingConstructorPromotableParameters80Cases
     *
     * @requires PHP ^8.0
     */
    public function testGettingConstructorPromotableParameters80(array $expected, string $code): void
    {
        $tokens = Tokens::fromCode($code);
        $analysis = new ConstructorAnalysis($tokens, 11);

        self::assertSame(11, $analysis->getConstructorIndex());
        self::assertSame($expected, $analysis->getConstructorPromotableParameters());
    }

    /**
     * @return iterable<array{array<int, string>, string}>
     */
    public static function provideGettingConstructorPromotableParameters80Cases(): iterable
    {
        yield 'some already promoted' => [
            [22 => '$b', 39 => '$s'],
            '<?php class Foo {
                    public function __construct(public array $a, bool $b, protected ?Bar\Baz\Qux $q, string $s, private OtherType $t) {}
                }',
        ];
    }

    /**
     * @param array<string, int> $expected
     *
     * @dataProvider provideGettingConstructorPromotableAssignmentsCases
     */
    public function testGettingConstructorPromotableAssignments(array $expected, string $code): void
    {
        $tokens = Tokens::fromCode($code);
        $analysis = new ConstructorAnalysis($tokens, 11);

        self::assertSame($expected, $analysis->getConstructorPromotableAssignments());
    }

    /**
     * @return iterable<array{array<string, int>, string}>
     */
    public static function provideGettingConstructorPromotableAssignmentsCases(): iterable
    {
        yield 'simple assignments' => [
            ['$x' => 30, '$y' => 39, '$z' => 48],
            '<?php class Foo {
                public function __construct($x, $y, $z) {
                    $this->a = $x;
                    $this->b = $y;
                    $this->c = $z;
                }
            }',
        ];

        yield 'duplicated assigned parameters' => [
            ['$x' => 30, '$z' => 59],
            '<?php class Foo {
                public function __construct($x, $y, $z) {
                    $this->a = $x;
                    $this->b = $y;
                    $this->c = $y; // $y is assigned for 2nd time
                    $this->d = $z;
                }
            }',
        ];

        yield 'duplicated assigned properties' => [
            ['$x' => 30],
            '<?php class Foo {
                public function __construct($x, $y, $z) {
                    $this->a = $x;
                    $this->b = $y;
                    $this->b = $z; // $this->b is assigned for 2nd time
                }
            }',
        ];

        yield 'not allowed assignment' => [
            ['$e' => 86],
            '<?php class Foo {
                public function __construct($a, $b, $c, $d, $e) {
                    $this->a = $a + 1;
                    $notThis->b = $b;
                    $this->c = 1 + $c;
                    $this->d = $this->d2 = $d;
                    $this->e = $e;
                }
            }',
        ];

        yield 'nested assignment' => [
            ['$x' => 30, '$z' => 60],
            '<?php class Foo {
                public function __construct($x, $y, $z) {
                    $this->x = $x;
                    if (shouldBeAssigned()) {
                        $this->y = $y;
                    }
                    $this->z = $z;
                }
            }',
        ];

        yield 'variable property assignment' => [
            [],
            '<?php class Foo {
                public function __construct($x) {
                    $this->$x = $x;
                }
            }',
        ];
    }
}
