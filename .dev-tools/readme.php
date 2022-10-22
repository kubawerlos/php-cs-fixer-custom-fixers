<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

require_once __DIR__ . '/autoload.php';

$application = new Symfony\Component\Console\Application();
$command = new PhpCsFixerCustomFixersDev\Readme\ReadmeCommand('readme');

$application->add($command);

$application->setDefaultCommand($command->getName(), true);
$application->run();
