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
            [15 => 'a', 20 => 'b', 25 => 'i'],
            '<?php class Foo {
                public function __construct(array $a, bool $b, int $i) {}
            }',
        ];

        yield 'parameters without types are not supported' => [
            [21 => 'i'],
            '<?php class Foo {
                public function __construct($noType1, $noType2, int $i, $noType3) {}
            }',
        ];

        yield 'callable is not supported' => [
            [15 => 'a', 20 => 'b', 35 => 'i'],
            '<?php class Foo {
                public function __construct(array $a, bool $b, callable $c1, CALLABLE $c1, int $i) {}
            }',
        ];

        if (\PHP_VERSION_ID >= 80000) {
            yield 'some already promoted' => [
                [22 => 'b', 39 => 's'],
                '<?php class Foo {
                public function __construct(public array $a, bool $b, protected ?Bar\Baz\Qux $q, string $s, private OtherType $t) {}
            }',
            ];
        }
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
            ['x' => 30, 'y' => 39, 'z' => 48],
            '<?php class Foo {
                public function __construct($x, $y, $z) {
                    $this->a = $x;
                    $this->b = $y;
                    $this->c = $z;
                }
            }',
        ];

        yield 'duplicated assigned parameters' => [
            ['x' => 30, 'z' => 59],
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
            ['x' => 30],
            '<?php class Foo {
                public function __construct($x, $y, $z) {
                    $this->a = $x;
                    $this->b = $y;
                    $this->b = $z; // $this->b is assigned for 2nd time
                }
            }',
        ];

        yield 'not allowed assignment' => [
            ['e' => 86],
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
    }
}
