<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Adapter;

use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class PhpUnitTestCaseIndicatorAdapter
{
    public static function findPhpUnitClasses(Tokens $tokens): iterable
    {
        return (new PhpUnitTestCaseIndicator())->findPhpUnitClasses($tokens);
    }
}
