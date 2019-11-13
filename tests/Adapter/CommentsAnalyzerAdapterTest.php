<?php

declare(strict_types = 1);

namespace Tests\Adapter;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\CommentsAnalyzerAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Adapter\CommentsAnalyzerAdapter
 */
final class CommentsAnalyzerAdapterTest extends TestCase
{
    public function testGetCommentBlockIndices(): void
    {
        $tokens = Tokens::fromCode('<?php
            // Foo
            // Bar
        ');
        $adapter = new CommentsAnalyzerAdapter();

        static::assertSame(
            [2, 4],
            $adapter->getCommentBlockIndices($tokens, 2)
        );
    }
}
