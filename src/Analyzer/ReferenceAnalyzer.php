<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Analyzer;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class ReferenceAnalyzer
{
    public function isReference(Tokens $tokens, int $index): bool
    {
        if ($tokens[$index]->isGivenKind(CT::T_RETURN_REF)) {
            return true;
        }

        if (!$tokens[$index]->equals('&')) {
            return false;
        }

        /** @var int $index */
        $index = $tokens->getPrevMeaningfulToken($index);
        if ($tokens[$index]->equalsAny(['=', [T_AS], [T_CALLABLE], [T_DOUBLE_ARROW], [CT::T_ARRAY_TYPEHINT]])) {
            return true;
        }

        if ($tokens[$index]->isGivenKind(T_STRING)) {
            $index = $tokens->getPrevMeaningfulToken($index);
        }

        return $tokens[$index]->equalsAny(['(', ',', [T_NS_SEPARATOR], [CT::T_NULLABLE_TYPE]]);
    }
}
