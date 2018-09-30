<?php

declare(strict_types = 1);

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
