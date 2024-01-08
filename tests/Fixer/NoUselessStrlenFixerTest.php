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
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUselessStrlenFixer
 */
final class NoUselessStrlenFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertRiskiness(true);
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
        yield ['<?php Foo\strlen($s) > 0;'];
        yield ['<?php strsize($s) > 0;'];
        yield ['<?php strlen($s) > 1;'];
        yield ['<?php 1 < strlen($s);'];
        yield ['<?php strlen() > 0;'];
        yield ['<?php strlen($a, $b) > 0;'];

        yield ['<?php $s !== \'\';', '<?php strlen($s) > 0;'];
        yield ['<?php $s === \'\';', '<?php strlen($s) === 0;'];
        yield ['<?php $s == \'\';', '<?php strlen($s) == 0;'];
        yield ['<?php $s !== \'\';', '<?php strlen($s) !== 0;'];
        yield ['<?php $s != \'\';', '<?php strlen($s) != 0;'];

        yield ['<?php \'\' !== $s;', '<?php 0 < strlen($s);'];
        yield ['<?php \'\' === $s;', '<?php 0 === strlen($s);'];
        yield ['<?php \'\' == $s;', '<?php 0 == strlen($s);'];
        yield ['<?php \'\' !== $s;', '<?php 0 !== \strlen($s);'];
        yield ['<?php \'\' != $s;', '<?php 0 != \strlen($s);'];

        yield ['<?php $s !== \'\';', '<?php \strlen($s) > 0;'];
        yield ['<?php $s !== \'\';', '<?php mb_strlen($s) > 0;'];
        yield ['<?php $s !== \'\';', '<?php StrLen($s) > 0;'];
        yield ['<?php $s !== \'\';', '<?php MB_strlen($s) > 0;'];
        yield ['<?php $s !== \'\';', '<?php \mb_strlen($s) > 0;'];

        yield ['<?php $s !== \'\';', '<?php strlen ( $s ) > 0;'];
    }
}
