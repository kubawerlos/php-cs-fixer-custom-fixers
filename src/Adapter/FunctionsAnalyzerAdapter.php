<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Adapter;

use PhpCsFixer\Tokenizer\Analyzer\Analysis\TypeAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class FunctionsAnalyzerAdapter
{
    /** @var FunctionsAnalyzer */
    private $functionsAnalyzer;

    public function __construct()
    {
        $this->functionsAnalyzer = new FunctionsAnalyzer();
    }

    public function isGlobalFunctionCall(Tokens $token, int $index): bool
    {
        return $this->functionsAnalyzer->isGlobalFunctionCall($token, $index);
    }

    public function getFunctionReturnType(Tokens $token, int $index): ?TypeAnalysis
    {
        return $this->functionsAnalyzer->getFunctionReturnType($token, $index);
    }
}
