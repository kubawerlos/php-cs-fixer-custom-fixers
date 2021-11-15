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

        $analyzer->findConstructor($tokens, 2, true);
    }

    /**
     * @param array<int, null|int> $expected
     *
     * @dataProvider provideFindingNonAbstractConstructorCases
     */
    public function testFindingNonAbstractConstructor(array $expected, bool $allowAbstract, string $code): void
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new ConstructorAnalyzer();

        foreach ($expected as $classIndex => $nonAbstractConstructorIndex) {
            $constructorAnalysis = $analyzer->findConstructor($tokens, $classIndex, $allowAbstract);

            if ($nonAbstractConstructorIndex === null) {
                self::assertNull($constructorAnalysis);
            } else {
                self::assertInstanceOf(ConstructorAnalysis::class, $constructorAnalysis);
                self::assertSame($nonAbstractConstructorIndex, $constructorAnalysis->getConstructorIndex());
            }
        }
    }

    /**
     * @return iterable<array{array<int, null|int>, bool, string}>
     */
    public static function provideFindingNonAbstractConstructorCases(): iterable
    {
        yield 'no constructor' => [
            [3 => null],
            true,
            '<?php abstract class Foo {
                public function notConstructor() {}
            }',
        ];

        yield 'abstract constructor allowed to be found' => [
            [3 => 13],
            true,
            '<?php abstract class Foo {
                abstract public function __construct() {}
            }',
        ];

        yield 'abstract constructor not allowed to be found' => [
            [3 => null],
            false,
            '<?php abstract class Foo {
                abstract public function __construct() {}
            }',
        ];

        yield 'public constructor' => [
            [3 => 11],
            true,
            '<?php abstract class Foo {
                public function __construct() {}
            }',
        ];

        yield 'uppercase constructor' => [
            [3 => 11],
            true,
            '<?php abstract class Foo {
                public function __CONSTRUCT() {}
            }',
        ];

        yield 'class with other elements' => [
            [3 => 29],
            true,
            '<?php abstract class Foo {
                public $a;
                public static function create() {}
                public function __construct() {}
                public function bar() {}
            }',
        ];

        yield 'multiple classes' => [
            [2 => 10, 21 => null, 29 => 37],
            true,
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
