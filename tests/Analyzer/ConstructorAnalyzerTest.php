<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Analyzer;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ConstructorAnalysis;
use PhpCsFixerCustomFixers\Analyzer\ConstructorAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\ConstructorAnalyzer
 */
final class ConstructorAnalyzerTest extends TestCase
{
    public function testFindingConstructorWhenNotForClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Index 2 is not a class.');

        $tokens = Tokens::fromCode('<?php $no . $class . $here;');
        $analyzer = new ConstructorAnalyzer();

        $analyzer->findNonAbstractConstructor($tokens, 2);
    }

    /**
     * @param array<int, null|int> $expected
     *
     * @dataProvider provideFindingNonAbstractConstructorCases
     */
    public function testFindingNonAbstractConstructor(array $expected, string $code): void
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new ConstructorAnalyzer();

        foreach ($expected as $classIndex => $nonAbstractConstructorIndex) {
            $constructorAnalysis = $analyzer->findNonAbstractConstructor($tokens, $classIndex);

            if ($nonAbstractConstructorIndex === null) {
                self::assertNull($constructorAnalysis);
            } else {
                self::assertInstanceOf(ConstructorAnalysis::class, $constructorAnalysis);
                self::assertSame($nonAbstractConstructorIndex, $constructorAnalysis->getConstructorIndex());
            }
        }
    }

    /**
     * @return iterable<array{array<int, null|int>, string}>
     */
    public static function provideFindingNonAbstractConstructorCases(): iterable
    {
        yield 'no constructor' => [
            [3 => null],
            '<?php abstract class Foo {
                public function notConstructor() {}
            }',
        ];

        yield 'abstract constructor' => [
            [3 => null],
            '<?php abstract class Foo {
                abstract public function __construct() {}
            }',
        ];

        yield 'public constructor' => [
            [3 => 11],
            '<?php abstract class Foo {
                public function __construct() {}
            }',
        ];

        yield 'uppercase constructor' => [
            [3 => 11],
            '<?php abstract class Foo {
                public function __CONSTRUCT() {}
            }',
        ];

        yield 'class with other elements' => [
            [3 => 29],
            '<?php abstract class Foo {
                public $a;
                public static function create() {}
                public function __construct() {}
                public function bar() {}
            }',
        ];

        yield 'multiple classes' => [
            [2 => 10, 21 => null, 29 => 37],
            '<?php
            class Foo {
                public function __construct() {}
            }
            class Bar {
            }
            class Baz {
                public function __construct() {}
            }',
        ];
    }
}
