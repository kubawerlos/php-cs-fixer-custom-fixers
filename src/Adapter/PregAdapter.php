<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Adapter;

use PhpCsFixer\Preg;

/**
 * @internal
 */
final class PregAdapter
{
    /**
     * @param null|string[] $matches
     */
    public static function match(string $pattern, string $subject, ?array &$matches = null): int
    {
        return Preg::match($pattern, $subject, $matches);
    }

    /**
     * @param null|string[] $matches
     */
    public static function matchAll(string $pattern, string $subject, ?array &$matches = null): int
    {
        return Preg::matchAll($pattern, $subject, $matches);
    }

    /**
     * @param string|string[] $pattern
     * @param string|string[] $replacement
     * @param string|string[] $subject
     *
     * @return string|string[]
     */
    public static function replace($pattern, $replacement, $subject, int $limit = -1)
    {
        return Preg::replace($pattern, $replacement, $subject, $limit);
    }
}
