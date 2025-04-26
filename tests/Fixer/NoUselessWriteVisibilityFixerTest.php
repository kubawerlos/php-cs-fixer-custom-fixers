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
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUselessWriteVisibilityFixer
 *
 * @requires PHP >= 8.4
 */
final class NoUselessWriteVisibilityFixerTest extends AbstractFixerTestCase
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
     * @return iterable<string, array{0: string, 1?: string}>
     */
    public static function provideFixCases(): iterable
    {
        yield 'class properties' => [
            <<<'PHP'
                <?php class Foo {
                    public int $x;
                    protected int $y;
                    private int $z;
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public public(set) int $x;
                    protected protected(set) int $y;
                    private private(set) int $z;
                }
                PHP,
        ];

        yield 'only write visibility' => [
            <<<'PHP'
                <?php class Foo {
                    public string $a;
                    public string $b;
                    public function __construct(
                        public string $x,
                        public string $y,
                    ) {}
                    public string $c;
                    public string $d;
                }
                abstract class Bar {
                    abstract public function __construct();
                    public string $a;
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public(set) string $a;
                    public(set) string $b;
                    public function __construct(
                        public(set) string $x,
                        public(set) string $y,
                    ) {}
                    public(set) string $c;
                    public(set) string $d;
                }
                abstract class Bar {
                    abstract public function __construct();
                    public(set) string $a;
                }
                PHP,
        ];

        yield 'promoted properties' => [
            <<<'PHP'
                <?php class Foo {
                    public function __construct(
                        public string $x,
                        protected string $y,
                        private string $z,
                    ) {}
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public function __construct(
                        public public(set) string $x,
                        protected protected(set) string $y,
                        private private(set) string $z,
                    ) {}
                }
                PHP,
        ];

        yield 'abstract property' => [
            <<<'PHP'
                <?php abstract class Foo {
                    public abstract int $x { get; }
                }
                PHP,
            <<<'PHP'
                <?php abstract class Foo {
                    public abstract public(set) int $x { get; }
                }
                PHP,
        ];
    }
}
