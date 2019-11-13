<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Adapter;

use PhpCsFixer\Utils;

/**
 * @internal
 */
final class UtilsAdapter
{
    /**
     * @param string[] $names
     */
    public static function naturalLanguageJoinWithBackticks(array $names): string
    {
        return Utils::naturalLanguageJoinWithBackticks($names);
    }
}
