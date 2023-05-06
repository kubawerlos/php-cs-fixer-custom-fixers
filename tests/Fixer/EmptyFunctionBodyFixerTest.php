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
 * @covers \PhpCsFixerCustomFixers\Fixer\EmptyFunctionBodyFixer
 */
final class EmptyFunctionBodyFixerTest extends AbstractFixerTestCase
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
        yield 'non-empty body' => [
            '<?php
                function f1()
                { /* foo */ }
                function f2()
                { /** foo */ }
                function f3()
                { // foo
                }
                function f4()
                {
                    return true;
                }
            ',
        ];

        yield 'functions' => [
            '<?php
                function notThis1()    { return 1; }
                function f1() {}
                function f2() {}
                function f3() {}
                function notThis2(){ return 1; }
            ',
            '<?php
                function notThis1()    { return 1; }
                function f1()
                {}
                function f2() {
                }
                function f3()
                {
                }
                function notThis2(){ return 1; }
            ',
        ];

        yield 'remove spaces' => [
            '<?php
                function f1() {}
                function f2() {}
                function f3() {}
            ',
            '<?php
                function f1() { }
                function f2() { }
                function f3() { }
            ',
        ];

        yield 'add spaces' => [
            '<?php
                function f1() {}
                function f2() {}
                function f3() {}
            ',
            '<?php
                function f1(){}
                function f2(){}
                function f3(){}
            ',
        ];

        yield 'with return types' => [
            '<?php
                function f1(): void {}
                function f2(): \Foo\Bar {}
                function f3(): ?string {}
            ',
            '<?php
                function f1(): void
                {}
                function f2(): \Foo\Bar    {    }
                function f3(): ?string {


                }
            ',
        ];

        yield 'abstract function' => [
            '<?php abstract class C {
                abstract function f1();
                function f2() {}
                abstract function f3();
            }
            if (true)    {    }
            ',
            '<?php abstract class C {
                abstract function f1();
                function f2()    {    }
                abstract function f3();
            }
            if (true)    {    }
            ',
        ];

        yield 'every token in separate line' => [
            '<?php
                function
                foo
                (
                )
                :
                void {}
            ',
            '<?php
                function
                foo
                (
                )
                :
                void
                {
                }
            ',
        ];

        yield 'comment before body' => [
            '<?php
                function f1()
                // foo
                {}
                function f2()
                /* foo */
                {}
                function f3()
                /** foo */
                {}
                function f4()
                /** foo */
                /** bar */
                {}
            ',
            '<?php
                function f1()
                // foo
                {
                }
                function f2()
                /* foo */
                {

                }
                function f3()
                /** foo */
                {
                }
                function f4()
                /** foo */
                /** bar */
                {    }
            ',
        ];
    }
}
