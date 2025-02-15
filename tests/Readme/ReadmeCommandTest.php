<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Readme;

use PhpCsFixerCustomFixersDev\Readme\ReadmeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @requires PHP 8.2
 *
 * @covers \PhpCsFixerCustomFixersDev\Readme\ReadmeCommand
 */
final class ReadmeCommandTest extends TestCase
{
    public function testReadmeIsUpToDate(): void
    {
        $tester = new CommandTester(new ReadmeCommand());

        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/../../README.md',
            $tester->getDisplay(),
            'README.md is not up to date, run "composer fix" to update it.',
        );
    }

    public function testNumberOfTests(): void
    {
        \preg_match(
            '~https://img.shields.io/badge/tests-(\\d+)-brightgreen.svg~',
            (string) \file_get_contents(__DIR__ . '/../../README.md'),
            $matches,
        );
        self::assertArrayHasKey(1, $matches);

        $expectedNumberOfTests = (int) $matches[1];

        $readmeCommand = new \ReflectionClass(ReadmeCommand::class);
        $numberOfTests = $readmeCommand->getMethod('numberOfTests');
        $numberOfTests->setAccessible(true);
        $actualNumberOfTests = $numberOfTests->invoke($readmeCommand->newInstance());

        self::assertSame($expectedNumberOfTests, $actualNumberOfTests);
    }
}
