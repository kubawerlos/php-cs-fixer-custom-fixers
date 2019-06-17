<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoReferenceInFunctionDefinitionFixer
 */
final class NoReferenceInFunctionDefinitionFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertSame(0, $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertTrue($this->fixer->isRisky());
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
            '<?php function foo($x) {}',
            '<?php function foo(&$x) {}',
        ];

        yield [
            '<?php foo(function () {}) & $x;',
        ];

        yield [
            '<?php function foo($x, $y, $z) {}',
            '<?php function foo(&$x, &$y, &$z) {}',
        ];

        yield [
            '<?php function foo(   $x   ) {}',
            '<?php function foo( &  $x   ) {}',
        ];

        yield [
            '<?php function ($x) { return $x; };',
            '<?php function (&$x) { return $x; };',
        ];

        yield [
            '<?php function foo($x) { return function ($x) { return $x; }; }',
            '<?php function foo($x) { return function (&$x) { return $x; }; }',
        ];

        yield [
            '<?php function ($x) { return function ($y) { return $y; }; };',
            '<?php function (&$x) { return function (&$y) { return $y; }; };',
        ];
    }
}
