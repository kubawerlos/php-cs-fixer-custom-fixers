<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Adapter;

use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class NamespacesAnalyzerAdapter
{
    /**
     * @return NamespaceAnalysis[]
     */
    public static function getDeclarations(Tokens $tokens): array
    {
        return (new NamespacesAnalyzer())->getDeclarations($tokens);
    }
}
