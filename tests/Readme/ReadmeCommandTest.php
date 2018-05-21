<?php

declare(strict_types = 1);

namespace Tests\Readme;

use PhpCsFixerCustomFixers\Readme\ReadmeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Readme\ReadmeCommand
 */
final class ReadmeCommandTest extends TestCase
{
    public function testReadmeIsUpToDate() : void
    {
        $application = new Application();
        $command     = new ReadmeCommand('readme');

        $application->add($command);
        $application->setDefaultCommand($command->getName(), true);
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $tester = new ApplicationTester($application);

        $tester->run([]);

        $this->assertStringEqualsFile(
            __DIR__ . '/../../README.md',
            $tester->getDisplay()
        );
    }
}
