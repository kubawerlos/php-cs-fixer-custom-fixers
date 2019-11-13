<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Adapter;

use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class ArgumentsAnalyzerAdapter
{
    /** @var ArgumentsAnalyzer */
    private $argumentsAnalyzer;

    public function __construct()
    {
        $this->argumentsAnalyzer = new ArgumentsAnalyzer();
    }

    public function countArguments(Tokens $tokens, int $openParenthesis, int $closeParenthesis): int
    {
        return $this->argumentsAnalyzer->countArguments($tokens, $openParenthesis, $closeParenthesis);
    }

    /**
     * @return array<int, int>
     */
    public function getArguments(Tokens $tokens, int $openParenthesis, int $closeParenthesis): array
    {
        return $this->argumentsAnalyzer->getArguments($tokens, $openParenthesis, $closeParenthesis);
    }
}
