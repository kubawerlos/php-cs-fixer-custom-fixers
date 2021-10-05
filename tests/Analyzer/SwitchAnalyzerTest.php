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
use PhpCsFixerCustomFixers\Analyzer\Analysis\CaseAnalysis;
use PhpCsFixerCustomFixers\Analyzer\Analysis\SwitchAnalysis;
use PhpCsFixerCustomFixers\Analyzer\SwitchAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\SwitchAnalyzer
 */
final class SwitchAnalyzerTest extends TestCase
{
    public function testForNotSwitch(): void
    {
        $analyzer = new SwitchAnalyzer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Index 3 is not "switch".');

        $analyzer->getSwitchAnalysis(Tokens::fromCode('<?php $a;$b;$c;'), 3);
    }

    /**
     * @dataProvider provideGettingSwitchAnalysisCases
     */
    public function testGettingSwitchAnalysis(SwitchAnalysis $expected, string $code, int $index): void
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new SwitchAnalyzer();

        self::assertSame(\serialize($expected), \serialize($analyzer->getSwitchAnalysis($tokens, $index)));
    }

    /**
     * @return iterable<array{SwitchAnalysis, string, int}>
     */
    public static function provideGettingSwitchAnalysisCases(): iterable
    {
        yield 'two cases' => [
            new SwitchAnalysis(7, 46, [new CaseAnalysis(12), new CaseAnalysis(39)]),
            '<?php switch ($foo) {
                case 1: $x = bar() ? 1 : 0; return true;
                case 2: return false;
            }',
            1,
        ];

        yield 'case without code' => [
            new SwitchAnalysis(7, 34, [new CaseAnalysis(12), new CaseAnalysis(22), new CaseAnalysis(27)]),
            '<?php switch ($foo) {
                case 1: return true;
                case 2:
                case 3: return false;
            }',
            1,
        ];

        yield 'advanced cases' => [
            new SwitchAnalysis(7, 132, [
                new CaseAnalysis(10),
                new CaseAnalysis(22),
                new CaseAnalysis(40),
                new CaseAnalysis(53),
                new CaseAnalysis(71),
                new CaseAnalysis(125),
            ]),
            '<?php switch (true) {
                default: return 0;
                case ("a"): return 1;
                case [1, 2, 3]: return 2;
                case getValue($foo): return 3;
                case getValue2($foo)["key"]->bar: return 4;
                case $a->$b::$c->${$d}->${$e}::foo(function ($x) { return $x * 2 + 2; })->$g::$h: return 5;
            }',
            1,
        ];

        yield 'two case and default' => [
            new SwitchAnalysis(7, 38, [new CaseAnalysis(12), new CaseAnalysis(22), new CaseAnalysis(30)]),
            '<?php switch ($foo) { case 10: return true; case 100: return false; default: return -1; }',
            1,
        ];

        yield 'two case and default with semicolon instead of colon' => [
            new SwitchAnalysis(7, 38, [new CaseAnalysis(12), new CaseAnalysis(22), new CaseAnalysis(30)]),
            '<?php switch ($foo) { case 10; return true; case 100; return false; default; return -1; }',
            1,
        ];

        yield 'ternary operator in case' => [
            new SwitchAnalysis(7, 39, [new CaseAnalysis(22), new CaseAnalysis(32)]),
            '<?php switch ($foo) { case ($bar ? 10 : 20): return true; case 100: return false; }',
            1,
        ];

        yield 'nested switch' => [
            new SwitchAnalysis(7, 67, [new CaseAnalysis(12), new CaseAnalysis(60)]),
            '<?php switch ($foo) { case 10:
                switch ($bar) { case "a": return "b"; case "c": return "d"; case "e": return "f"; }
                return;
                case 100: return false; }',
            1,
        ];

        yield 'switch in case' => [
            new SwitchAnalysis(7, 98, [new CaseAnalysis(81), new CaseAnalysis(91)]),
            '<?php switch ($foo) { case (
                array_sum(array_map(function ($x) { switch ($bar) { case "a": return "b"; case "c": return "d"; case "e": return "f"; } }, [1, 2, 3]))
            ): return true; case 100: return false; }',
            1,
        ];

        yield 'function with nullable parameter' => [
            new SwitchAnalysis(7, 43, [new CaseAnalysis(12), new CaseAnalysis(36)]),
            '<?php switch ($foo) { case 10: function foo(?int $x) {}; return true; case 100: return false; }',
            1,
        ];

        yield 'function with return type' => [
            new SwitchAnalysis(7, 43, [new CaseAnalysis(12), new CaseAnalysis(36)]),
            '<?php switch ($foo) { case 10: function foo($x): int {}; return true; case 100: return false; }',
            1,
        ];

        yield 'alternative syntax' => [
            new SwitchAnalysis(7, 30, [new CaseAnalysis(12), new CaseAnalysis(22)]),
            '<?php switch ($foo) : case 10: return true; case 100: return false; endswitch;',
            1,
        ];

        yield 'alternative syntax with closing tag' => [
            new SwitchAnalysis(7, 29, [new CaseAnalysis(12), new CaseAnalysis(22)]),
            '<?php switch ($foo) : case 10: return true; case 100: return false; endswitch ?>',
            1,
        ];

        yield 'alternative syntax nested' => [
            new SwitchAnalysis(7, 69, [new CaseAnalysis(12), new CaseAnalysis(61)]),
            '<?php switch ($foo) : case 10:
                switch ($bar) : case "a": return "b"; case "c": return "d"; case "e": return "f"; endswitch;
                return;
                case 100: return false; endswitch;',
            1,
        ];

        yield 'alternative syntax nested with mixed colon/semicolon' => [
            new SwitchAnalysis(7, 69, [new CaseAnalysis(12), new CaseAnalysis(61)]),
            '<?php switch ($foo) : case 10;
                switch ($bar) : case "a": return "b"; case "c"; return "d"; case "e": return "f"; endswitch;
                return;
                case 100: return false; endswitch;',
            1,
        ];
    }
}
