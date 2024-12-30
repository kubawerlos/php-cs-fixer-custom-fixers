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
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUselessDirnameCallFixer
 */
final class NoUselessDirnameCallFixerTest extends AbstractFixerTestCase
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
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFixCases(): iterable
    {
        yield ['<?php Vendor\\dirname(__DIR__) . "/path";'];
        yield ['<?php dearname(__DIR__) . "/path";'];
        yield ['<?php dirname(__DIR__, LEVEL) . "/path";'];
        yield ['<?php dirname(__DIR__) . $path;'];
        yield ['<?php dirname(__DIR__); "/path";'];

        yield [
            "<?php __DIR__ . '/../path';",
            "<?php dirname(__DIR__) . '/path';",
        ];

        yield [
            '<?php __DIR__ . "/../path";',
            '<?php dirname(__DIR__) . "/path";',
        ];

        yield [
            '<?php __DIR__ . "/../path";',
            '<?php \\dirname(__DIR__) . "/path";',
        ];

        yield [
            '<?php __DIR__ . "/../../../path";',
            '<?php \\dirname(__DIR__,3) . "/path";',
        ];

        yield [
            '<?php __DIR__ . "/../path";',
            '<?php DIRNAME(__DIR__) . "/path";',
        ];

        yield [
            "<?php   __DIR__  . '/../path';",
            "<?php dirname ( __DIR__ ) . '/path';",
        ];

        yield [
            '<?php
                __DIR__ . "/../path1";
                foo(__DIR__) . "/path2";
                dirname(__DIR__, $level) . "/path3";
                __DIR__ . "/../path4";
            ',
            '<?php
                dirname(__DIR__) . "/path1";
                foo(__DIR__) . "/path2";
                dirname(__DIR__, $level) . "/path3";
                dirname(__DIR__) . "/path4";
            ',
        ];

        // test with trailing comma
        yield [
            '<?php __DIR__ . "/../path";',
            '<?php dirname(__DIR__,) . "/path";',
        ];

        yield [
            '<?php __DIR__ . "/../../../path";',
            '<?php \\dirname(__DIR__,3,) . "/path";',
        ];
    }
}
