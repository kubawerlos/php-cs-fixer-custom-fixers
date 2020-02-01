<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\DataProviderReturnTypeFixer
 */
final class DataProviderReturnTypeFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertTrue($this->fixer->isRisky());
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public static function provideFixCases(): iterable
    {
        yield 'data provider with iterable return type' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     */
    public function testFoo() {}
    public function provideFooCases() : iterable {}
}',
        ];

        $template = '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     */
    public function testFoo() {}
    /**
     * @dataProvider provider
     */
    public function testBar() {}
    public function provideFooCases()%s {}
    public function provider()%s {}
    public function notProvider(): array {}
}';

        $cases = [
            'data provider without return type' => [
                ': iterable',
                '',
            ],
            'data provider with array return type' => [
                ': iterable',
                ': array',
            ],
            'data provider with return type and comment' => [
                ': /* TODO: add more cases */ iterable',
                ': /* TODO: add more cases */ array',
            ],
            'data provider with return type namespaced class' => [
                ': iterable',
                ': Foo\Bar',
            ],
            'data provider with return type namespaced class starting with iterable' => [
                ': iterable',
                ': iterable \ Foo',
            ],
            'data provider with return type namespaced class and comments' => [
                ': iterable',
                ': Foo/* Some info */\/* More info */Bar',
            ],
            'data provider with iterable return type in different case' => [
                ': iterable',
                ': Iterable',
            ],
        ];

        foreach ($cases as $key => $case) {
            yield $key => \array_map(
                static function (string $code) use ($template): string {
                    return \sprintf($template, $code, $code);
                },
                $case
            );
        }

        yield 'multiple data providers' => [
            '<?php class FooTest extends TestCase {
                /**
                 * @dataProvider provider4
                 * @dataProvider provider1
                 * @dataProvider provider5
                 * @dataProvider provider6
                 * @dataProvider provider2
                 * @dataProvider provider3
                 */
                public function testFoo() {}
                public function provider1(): iterable {}
                public function provider2(): iterable {}
                public function provider3(): iterable {}
                public function provider4(): iterable {}
                public function provider5(): iterable {}
                public function provider6(): iterable {}
            }',
            '<?php class FooTest extends TestCase {
                /**
                 * @dataProvider provider4
                 * @dataProvider provider1
                 * @dataProvider provider5
                 * @dataProvider provider6
                 * @dataProvider provider2
                 * @dataProvider provider3
                 */
                public function testFoo() {}
                public function provider1() {}
                public function provider2() {}
                public function provider3() {}
                public function provider4() {}
                public function provider5() {}
                public function provider6() {}
            }',
        ];

        yield 'advanced case' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     * @dataProvider provideFooCases2
     */
    public function testFoo()
    {
        /**
         * @dataProvider someFunction
         */
        $foo = /** foo */ function ($x) { return $x + 1; };
        /**
         * @dataProvider someFunction2
         */
        /* foo */someFunction2();
    }
    /**
     * @dataProvider provideFooCases3
     */
    public function testBar() {}

    public function provideFooCases(): iterable {}
    public function provideFooCases2(): iterable {}
    public function provideFooCases3(): iterable {}
    public function someFunction() {}
    public function someFunction2() {}
}',
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     * @dataProvider provideFooCases2
     */
    public function testFoo()
    {
        /**
         * @dataProvider someFunction
         */
        $foo = /** foo */ function ($x) { return $x + 1; };
        /**
         * @dataProvider someFunction2
         */
        /* foo */someFunction2();
    }
    /**
     * @dataProvider provideFooCases3
     */
    public function testBar() {}

    public function provideFooCases() {}
    public function provideFooCases2() {}
    public function provideFooCases3() {}
    public function someFunction() {}
    public function someFunction2() {}
}',
        ];

        foreach (['abstract', 'final', 'private', 'protected', 'static', '/* private */'] as $modifier) {
            yield \sprintf('test function with %s modifier', $modifier) => [
                \sprintf('<?php
                    class FooTest extends TestCase {
                        /**
                         * @dataProvider provideFooCases
                         */
                        %s function testFoo() {}
                        public function provideFooCases(): iterable {}
                    }
                ', $modifier),
                \sprintf('<?php
                    class FooTest extends TestCase {
                        /**
                         * @dataProvider provideFooCases
                         */
                        %s function testFoo() {}
                        public function provideFooCases() {}
                    }
                ', $modifier),
            ];
        }
    }
}
