<?php

declare(strict_types = 1);

namespace Tests\Adapter;

use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceUseAnalysis;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\NamespaceUsesAnalyzerAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Adapter\NamespaceUsesAnalyzerAdapter
 */
final class NamespaceUsesAnalyzerAdapterTest extends TestCase
{
    public function testGetDeclarationsFromTokens(): void
    {
        $tokens = Tokens::fromCode('<?php
            use Foo;
            use Bar;
        ');
        static::assertSame(
            \serialize([
                new NamespaceUseAnalysis('Foo', 'Foo', false, 2, 5, NamespaceUseAnalysis::TYPE_CLASS),
                new NamespaceUseAnalysis('Bar', 'Bar', false, 7, 10, NamespaceUseAnalysis::TYPE_CLASS),
            ]),
            \serialize(NamespaceUsesAnalyzerAdapter::getDeclarationsFromTokens($tokens))
        );
    }
}
