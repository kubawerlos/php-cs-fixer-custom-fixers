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

        while (($index = $tokens->getPrevMeaningfulToken($index)) !== null) {
            if ($tokens[$index]->equalsAny(['=', '(', ','])) {
                return true;
            }
            if ($tokens[$index]->equalsAny([')', ';'])) {
                return false;
            }
        }

        return false;
    }
}
