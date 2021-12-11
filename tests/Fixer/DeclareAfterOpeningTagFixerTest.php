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
 * @covers \PhpCsFixerCustomFixers\Fixer\DeclareAfterOpeningTagFixer
 */
final class DeclareAfterOpeningTagFixerTest extends AbstractFixerTestCase
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
        yield 'skip files not starting with PHP opening tag' => ['<html></html>'];

        yield 'fix inside comments' => [
            '<?php declare(strict_types=1);
// Foo
// Bar
            ',
            '<?php
// Foo
declare(strict_types=1);
// Bar
            ',
        ];

        yield 'fix uppercase opening tag' => [
            '<?PHP declare(strict_types=1);
// Foo
class Foo {}
            ',
            '<?PHP
// Foo
declare(strict_types=1);
class Foo {}
            ',
        ];

        yield 'fix uppercase declare' => [
            '<?php DECLARE(strict_types=1);
// Foo
class Foo {}
            ',
            '<?php
// Foo
DECLARE(strict_types=1);
class Foo {}
            ',
        ];

        yield 'fix uppercase strict_types' => [
            '<?php declare(STRICT_TYPES=1);
// Foo
class Foo {}
            ',
            '<?php
// Foo
declare(STRICT_TYPES=1);
class Foo {}
            ',
        ];

        yield 'fix and clean up empty lines left' => [
            '<?php declare(strict_types=1);

/*
 * Header comment
 */

// code starts here
            ',
            '<?php

/*
 * Header comment
 */

declare(strict_types=1);

// code starts here
            ',
        ];

        yield 'fix and clean up empty lines above' => [
            '<?php declare(strict_types=1);

// Foo
            ',
            '<?php


declare(strict_types=1);

// Foo
            ',
        ];

        yield 'fix with other declares' => [
            '<?php declare(strict_types=1);
// Foo
declare(ticks=1);
// Bar
            ',
            '<?php
declare(strict_types=1);
// Foo
declare(ticks=1);
// Bar
            ',
        ];

        yield 'fix with other directives' => [
            '<?php declare(strict_types=1, ticks=1);
// Foo
// Bar
            ',
            '<?php
// Foo
declare(strict_types=1, ticks=1);
// Bar
            ',
        ];

        yield 'ignore declare when not for strict types' => [
            '<?php
// Foo
declare(ticks=1);
// Bar
            ',
        ];

        yield 'ignore declare when used with block mode' => [
            '<?php
// Foo
declare(ticks=1) {
    // Bar
}
            ',
        ];
    }
}
