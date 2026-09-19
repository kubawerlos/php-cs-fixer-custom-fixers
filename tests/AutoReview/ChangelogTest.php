<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\AutoReview;

use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
#[CoversNothing]
final class ChangelogTest extends TestCase
{
    /**
     * @dataProvider \Tests\AutoReview\SrcCodeTest::provideFixerCases
     */
    #[DataProviderExternal(SrcCodeTest::class, 'provideFixerCases')]
    public function testFixerAddingIsListed(FixerInterface $fixer): void
    {
        self::assertChangelogContains('Add ' . (new \ReflectionObject($fixer))->getShortName());
    }

    /**
     * @dataProvider \Tests\AutoReview\SrcCodeTest::provideFixerCases
     */
    #[DataProviderExternal(SrcCodeTest::class, 'provideFixerCases')]
    public function testFixerDeprecatingIsListed(FixerInterface $fixer): void
    {
        if (!$fixer instanceof DeprecatedFixerInterface) {
            $this->expectNotToPerformAssertions();

            return;
        }

        self::assertChangelogContains('Deprecate ' . (new \ReflectionObject($fixer))->getShortName());
    }

    private static function assertChangelogContains(string $content): void
    {
        static $changelog;

        if ($changelog === null) {
            $changelog = \file_get_contents(__DIR__ . '/../../CHANGELOG.md');
        }

        self::assertIsString($changelog);
        self::assertStringContainsString($content, $changelog);
    }
}
