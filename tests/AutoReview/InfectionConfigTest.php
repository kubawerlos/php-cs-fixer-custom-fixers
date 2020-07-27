<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\AutoReview;

use PhpCsFixerCustomFixersDev\InfectionConfigBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class InfectionConfigTest extends TestCase
{
    public function testConfigIsUpToDate(): void
    {
        if (\file_exists(__DIR__ . '/../../dev-tools/vendor/autoload.php')) {
            require_once __DIR__ . '/../../dev-tools/vendor/autoload.php';
        } else {
            self::markTestSkipped('Not tested when dev-tools not installed.');
        }

        $configBuilder = new InfectionConfigBuilder();
        $configFromBuilder = $configBuilder->build();

        $actualConfig = \json_decode(\file_get_contents(__DIR__ . '/../../dev-tools/infection.json'), true);

        self::assertSame($configFromBuilder, $actualConfig, 'Infection config is not up to date, run "php ./dev-tools/build-infection-config".');
    }
}
