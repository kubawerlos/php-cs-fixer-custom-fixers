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
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocPropertySortedFixer
 */
final class PhpdocPropertySortedFixerTest extends AbstractFixerTestCase
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
        yield ['<?php
/**
 * @return void
 */
'];

        yield ['<?php
/**
 * @customAnnotation Some text
 */
'];

        yield ['<?php
/**
 * @param string $x
 * @return int
 */
'];

        yield [
            '<?php
/**
 * @property string $aaa
 * @property bool $bbb
 * @property int $zzz
 */
class Foo {}
',
            '<?php
/**
 * @property int $zzz
 * @property string $aaa
 * @property bool $bbb
 */
class Foo {}
',
        ];

        yield [
            '<?php
/**
 * @property string $alpha
 * @property int $beta
 * @property bool $gamma
 */
class Example {}
',
            '<?php
/**
 * @property bool $gamma
 * @property string $alpha
 * @property int $beta
 */
class Example {}
',
        ];

        yield [
            '<?php
/**
 * @property array $data
 * @property string|null $name property comment
 * @property int $value
 */
',
            '<?php
/**
 * @property string|null $name property comment
 * @property int $value
 * @property array $data
 */
',
        ];

        yield [
            '<?php
/**
 * @property ClassA $classA
 * @property ClassB $classB
 * @property ClassC $classC
 */
',
            '<?php
/**
 * @property ClassC $classC
 * @property ClassA $classA
 * @property ClassB $classB
 */
',
        ];

        yield [
            '<?php
/**
 * @property mixed
 * @property bool $firstName
 * @property int $lastName
 */
',
            '<?php
/**
 * @property int $lastName
 * @property mixed
 * @property bool $firstName
 */
',
        ];

        yield [
            '<?php
/**
 * @property string $first
 * @property int $second
 *
 * @property array $fourth
 * @property bool $third
 */
',
            '<?php
/**
 * @property int $second
 * @property string $first
 *
 * @property bool $third
 * @property array $fourth
 */
',
        ];

        yield [
            '<?php
/**
 * @param string $x
 * @property int $count
 * @property string $name
 * @return bool
 */
function example($x) {}
',
            '<?php
/**
 * @param string $x
 * @property string $name
 * @property int $count
 * @return bool
 */
function example($x) {}
',
        ];
    }
}
