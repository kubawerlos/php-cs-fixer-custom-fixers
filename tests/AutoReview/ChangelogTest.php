<?php

declare(strict_types = 1);

namespace Tests\AutoReview;

use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ChangelogTest extends TestCase
{
    /**
     * @dataProvider \Tests\AutoReview\SrcCodeTest::provideFixerCases
     */
    public function testFixerAddingIsListed(FixerInterface $fixer): void
    {
        self::assertChangelogContains('Add ' . (new \ReflectionObject($fixer))->getShortName());
    }

    /**
     * @dataProvider \Tests\AutoReview\SrcCodeTest::provideFixerCases
     */
    public function testFixerDeprecatingIsListed(FixerInterface $fixer): void
    {
        if (!$fixer instanceof DeprecatedFixerInterface) {
            $this->addToAssertionCount(1);

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

        self::assertStringContainsString($content, $changelog);
    }
}
