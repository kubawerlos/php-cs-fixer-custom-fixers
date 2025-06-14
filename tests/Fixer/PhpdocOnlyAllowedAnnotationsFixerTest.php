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
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocOnlyAllowedAnnotationsFixer
 */
final class PhpdocOnlyAllowedAnnotationsFixerTest extends AbstractFixerTestCase
{
    public function testConfiguration(): void
    {
        $options = self::getConfigurationOptions();
        self::assertArrayHasKey(0, $options);
        self::assertSame('elements', $options[0]->getName());
    }

    public function testIsRisky(): void
    {
        self::assertRiskiness(false);
    }

    /**
     * @param array<string, list<string>> $configuration
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, array $configuration = []): void
    {
        $this->doTest($expected, $input, $configuration);
    }

    /**
     * @return iterable<array{0: string, 1: string, 2?: array<string, list<string>>}>
     */
    public static function provideFixCases(): iterable
    {
        yield [
            '<?php


',
            '<?php
/** @var string */
/** @author John Doe */
',
        ];

        yield [
            '<?php
/**
 */
',
            '<?php
/**
 * @param Foo $foo
 * @return Bar
 */
',
        ];

        yield [
            '<?php
/**
 * @param Foo $foo
 */
',
            '<?php
/**
 * @param Foo $foo
 * @return Bar
 */
',
            ['elements' => ['param']],
        ];

        yield [
            '<?php
/**
 * @ORM\\Id
 * @ORM\\Column(type="integer")
 */
',
            '<?php
/**
 * @ORM\\Id
 * @ORM\\GeneratedValue
 * @ORM\\Column(type="integer")
 */
',
            ['elements' => ['ORM\\Id', 'ORM\\Column']],
        ];

        yield [
            '<?php
/**
 * @foo
 * @bar
 */
/**
 * @foo
 */
',
            '<?php
/**
 * @foo
 * @bar
 * @baz
 */
/**
 * @foo
 * @foobar
 */
',
            ['elements' => ['foo', 'bar']],
        ];

        yield [
            '<?php
                /**
                 */
             ',
            '<?php
                /**
                 * @#
                 * @return Foo
                 */
             ',
        ];
    }
}
