<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Fixer;

/**
 * @internal
 */
interface DeprecatingFixerInterface
{
    /**
     * Returns the ID of pull request to PHP CS Fixer like https://github.com/FriendsOfPHP/PHP-CS-Fixer/pull/X.
     */
    public function getPullRequestId(): int;
}
