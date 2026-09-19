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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @requires PHP >= 8.4.0
 * @requires PHPUnit < 13.0.0
 *
 * @covers \PhpCsFixerCustomFixersDev\Readme\ReadmeCommand
 */
#[CoversClass(ReadmeCommand::class)]
#[RequiresPhp('>= 8.4.0')]
#[RequiresPhpunit('< 13.0.0')]
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
}
