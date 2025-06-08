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

use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocNoNamedArgumentsTagFixer
 */
final class PhpdocNoNamedArgumentsTagFixerTest extends AbstractFixerTestCase
{
    public function testConfiguration(): void
    {
        $options = self::getConfigurationOptions();
        self::assertArrayHasKey(0, $options);
        self::assertSame('description', $options[0]->getName());
        self::assertSame('', $options[0]->getDefault());
        self::assertArrayHasKey(1, $options);
        self::assertSame('directory', $options[1]->getName());
        self::assertSame('', $options[1]->getDefault());

        $fixer = self::getFixer();
        \assert($fixer instanceof ConfigurableFixerInterface);

        $invalidDirectory = __DIR__ . '/invalid';

        $this->expectException(InvalidFixerConfigurationException::class);
        $this->expectExceptionMessage(\sprintf('[%s] The directory "%s" does not exists.', $fixer->getName(), $invalidDirectory));

        $fixer->configure(['directory' => $invalidDirectory]);
    }

    public function testIsRisky(): void
    {
        self::assertRiskiness(false);
    }

    /**
     * @param array<string, int> $configuration
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, array $configuration = []): void
    {
        $this->doTest($expected, $input, $configuration);
    }

    /**
     * @return iterable<string, array{0: string, 1?: null|string, 2?: array{path_prefix?: string, description?: string}, 3?: string}>
     */
    public static function provideFixCases(): iterable
    {
        yield 'do not touch anonymous class' => [
            <<<'PHP'
                <?php
                new class () {};
                PHP,
        ];

        yield 'do not touch for different directory' => [
            <<<'PHP'
                <?php
                class Foo {}
                PHP,
            null,
            ['directory' => __DIR__ . '/../../src/Fixer'],
        ];

        yield 'create PHPDoc comment' => [
            <<<'PHP'
                <?php

                /**
                 * @no-named-arguments
                 */
                class Foo {}
                PHP,
            <<<'PHP'
                <?php
                class Foo {}
                PHP,
        ];

        yield 'change the description' => [
            <<<'PHP'
                <?php

                /**
                 * @no-named-arguments Some description
                 */
                class Foo {}
                PHP,
            <<<'PHP'
                <?php
                class Foo {}
                PHP,
            ['description' => 'Some description'],
        ];

        yield 'multiple classes' => [
            <<<'PHP'
                <?php

                /**
                 * @no-named-arguments Some description
                 */
                class Foo {}

                new class {};

                /**
                 * @no-named-arguments Some description
                 */
                class Bar {}
                PHP,
            <<<'PHP'
                <?php
                class Foo {}

                new class {};

                class Bar {}
                PHP,
            ['description' => 'Some description'],
        ];
    }
}
