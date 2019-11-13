<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Adapter;

use PhpCsFixer\Tokenizer\Analyzer\CommentsAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class CommentsAnalyzerAdapter
{
    /** @var CommentsAnalyzer */
    private $commentsAnalyzer;

    public function __construct()
    {
        $this->commentsAnalyzer = new CommentsAnalyzer();
    }

    public function getCommentBlockIndices(Tokens $token, int $index): ?array
    {
        return $this->commentsAnalyzer->getCommentBlockIndices($token, $index);
    }
}
