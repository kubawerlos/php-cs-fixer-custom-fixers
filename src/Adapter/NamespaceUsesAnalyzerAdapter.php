<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Adapter;

use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceUseAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class NamespaceUsesAnalyzerAdapter
{
    /**
     * @return NamespaceUseAnalysis[]
     */
    public static function getDeclarationsFromTokens(Tokens $tokens): array
    {
        return (new NamespaceUsesAnalyzer())->getDeclarationsFromTokens($tokens);
    }
}
