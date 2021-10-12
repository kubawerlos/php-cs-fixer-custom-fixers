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

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUselessIfConditionFixer
 */
final class NoUselessIfConditionFixerTest extends AbstractFixerTestCase
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
     * @return iterable<array{0: string, 1?: null|string, 2?: array<string, bool>}>
     */
    public static function provideFixCases(): iterable
    {
        yield ['<?php if (trueee) { return 42; }'];
        yield ['<?php if (true && maybeTrueOrMaybeFalse()) { return 42; }'];
        yield ['<?php if (false || maybeTrueOrMaybeFalse()) { return 42; }'];

        yield [
            '<?php  return 42; ',
            '<?php if (true) { return 42; }',
        ];

        yield [
            '<?php  return 42; ',
            '<?php if (TRUE) { return 42; }',
        ];

        yield [
            '<?php  return 42; ',
            '<?php if (\true) { return 42; }',
        ];

        yield [
            '<?php
                return 42;',
            '<?php
            if (true) {
                return 42;
            }',
        ];

        yield [
            '<?php ',
            '<?php if (false) { return 42; }',
        ];

        yield [
            '<?php ',
            '<?php if (FALSE) { return 42; }',
        ];

        yield [
            '<?php ',
            '<?php if (\FALSE) { return 42; }',
        ];

        yield [
            '<?php  return 42; return 0;',
            '<?php if (true) return 42; return 0;',
        ];

        yield [
            '<?php  return 42;  return 0;',
            '<?php if (true): return 42; endif; return 0;',
        ];

        yield [
            '<?php  return 0;',
            '<?php if (false) return 42; return 0;',
        ];

        yield [
            '<?php
                        return 42;',
            '<?php
            if (true) {
                if (true) {
                    if (true) {
                        return 42;
                    }
                }
            }',
        ];

        yield [
            '<?php
                return 1;
                if ($x > 0) {return 2;}
                return 3;
                if (true && $y > 0) {return 4;}
                return 5;
                return 6;
                return 7;
                return 8;
            ',
            '<?php
                if (true) {return 1;}
                if ($x > 0) {return 2;}
                if (true) {return 3;}
                if (true && $y > 0) {return 4;}
                if (true) {return 5;}
                if (true):return 6;endif;
                if (true):return 7;endif;
                if (true) {return 8;}
            ',
        ];

        // TODO: support with else

        // TODO: support with elseif
    }
}
