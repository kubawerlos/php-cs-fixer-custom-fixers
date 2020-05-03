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

namespace PhpCsFixerCustomFixers\Analyzer;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class ReferenceAnalyzer
{
    public function isReference(Tokens $tokens, int $index): bool
    {
        /** @var Token $token */
        $token = $tokens[$index];

        if ($token->isGivenKind(CT::T_RETURN_REF)) {
            return true;
        }

        if (!$token->equals('&')) {
            return false;
        }

        /** @var int $index */
        $index = $tokens->getPrevMeaningfulToken($index);

        /** @var Token $token */
        $token = $tokens[$index];

        if ($token->equalsAny(['=', [T_AS], [T_CALLABLE], [T_DOUBLE_ARROW], [CT::T_ARRAY_TYPEHINT]])) {
            return true;
        }

        if ($token->isGivenKind(T_STRING)) {
            /** @var int $index */
            $index = $tokens->getPrevMeaningfulToken($index);

            /** @var Token $token */
            $token = $tokens[$index];
        }

        return $token->equalsAny(['(', ',', [T_NS_SEPARATOR], [CT::T_NULLABLE_TYPE]]);
    }
}
