<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Adapter;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @internal
 */
final class TokensAnalyzerAdapter
{
    /** @var TokensAnalyzer */
    private $tokensAnalyzer;

    public function __construct(Tokens $tokens)
    {
        $this->tokensAnalyzer = new TokensAnalyzer($tokens);
    }

    public function isAnonymousClass(int $index): bool
    {
        return $this->tokensAnalyzer->isAnonymousClass($index);
    }
}
