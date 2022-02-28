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
 * @covers \PhpCsFixerCustomFixersDev\Readme\ReadmeCommand
 *
 * @requires PHP ^8.1
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
            $tester->getDisplay()
        );
    }
}
