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

use PhpCsFixerCustomFixers\Fixer\NoReferenceInFunctionDefinitionFixer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoReferenceInFunctionDefinitionFixer
 */
#[CoversClass(NoReferenceInFunctionDefinitionFixer::class)]
final class NoReferenceInFunctionDefinitionFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertRiskiness(true);
    }

    /**
     * @dataProvider provideFixCases
     */
    #[DataProvider('provideFixCases')]
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
