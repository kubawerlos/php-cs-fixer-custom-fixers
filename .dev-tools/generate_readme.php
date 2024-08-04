<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpCsFixerCustomFixersDev\Readme\ReadmeCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$command = new ReadmeCommand();

$application->add($command);

$application->setDefaultCommand($command->getName(), true);
$application->run();
