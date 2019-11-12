<?php

declare(strict_types = 1);

namespace Tests\Adapter;

use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\TokensAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Adapter\TokensAdapter
 */
final class TokensAdapterTest extends TestCase
{
    public function testClearAt(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('clearAt')->with(1);

        $tokensAdapter = new TokensAdapter($tokens);
        $tokensAdapter->clearAt(1);
    }

    public function testClearRange(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('clearRange')->with(1, 2);

        $tokensAdapter = new TokensAdapter($tokens);
        $tokensAdapter->clearRange(1, 2);
    }

    public function testClearTokenAndMergeSurroundingWhitespace(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('clearTokenAndMergeSurroundingWhitespace')->with(1);

        $tokensAdapter = new TokensAdapter($tokens);
        $tokensAdapter->clearTokenAndMergeSurroundingWhitespace(1);
    }

    public function testCount(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->expects(static::exactly(2))->method('count')->willReturn(1);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame(1, $tokensAdapter->count());
    }

    public function testFindBlockEnd(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('findBlockEnd')->with(1, 2)->willReturn(3);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame(3, $tokensAdapter->findBlockEnd(1, 2));
    }

    public function testGetNextMeaningfulToken(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('getNextMeaningfulToken')->with(1)->willReturn(2);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame(2, $tokensAdapter->getNextMeaningfulToken(1));
    }

    public function testGteNextNonWhitespace(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('getNextNonWhitespace')->with(1)->willReturn(2);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame(2, $tokensAdapter->getNextNonWhitespace(1));
    }

    public function testGetNextTokenNonEmpty(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('getNonEmptySibling')->with(1, 1)->willReturn(2);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame(2, $tokensAdapter->getNextTokenNonEmpty(1));
    }

    public function testGetNextTokenNotOfKind(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('getTokenNotOfKindSibling')->with(1, 1, [])->willReturn(2);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame(2, $tokensAdapter->getNextTokenNotOfKind(1, []));
    }

    public function testGetNextTokenOfKind(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('getNextTokenOfKind')->with(1, [])->willReturn(2);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame(2, $tokensAdapter->getNextTokenOfKind(1, []));
    }

    public function testGetPrevMeaningfulToken(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('getPrevMeaningfulToken')->with(1)->willReturn(2);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame(2, $tokensAdapter->getPrevMeaningfulToken(1));
    }

    public function testGetPrevTokenNonEmpty(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('getNonEmptySibling')->with(1, -1)->willReturn(2);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame(2, $tokensAdapter->getPrevTokenNonEmpty(1));
    }

    public function testGetPrevTokenNotOfKind(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('getTokenNotOfKindSibling')->with(1, -1, [])->willReturn(2);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame(2, $tokensAdapter->getPrevTokenNotOfKind(1, []));
    }

    public function testGetPrevTokenOfKind(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('getPrevTokenOfKind')->with(1, [])->willReturn(2);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame(2, $tokensAdapter->getPrevTokenOfKind(1, []));
    }

    public function testHasNextMeaningfulToken(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('getNextMeaningfulToken')->with(1)->willReturn(true);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertTrue($tokensAdapter->hasNextMeaningfulToken(1));
    }

    public function testInsertAt(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('insertAt')->with(1, []);

        $tokensAdapter = new TokensAdapter($tokens);
        $tokensAdapter->insertAt(1, []);
    }

    public function testOffsetGet(): void
    {
        $token = $this->createMock(Token::class);

        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('offsetGet')->with(1)->willReturn($token);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame($token, $tokensAdapter[1]);
    }

    public function testOffsetSet(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('offsetSet')->with(1, 2);

        $tokensAdapter = new TokensAdapter($tokens);

        $tokensAdapter[1] = 2;
    }

    public function testOverrideRange(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('overrideRange')->with(1, 2, []);

        $tokensAdapter = new TokensAdapter($tokens);
        $tokensAdapter->overrideRange(1, 2, []);
    }

    public function testTtoArray(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);
        $tokens->expects(static::once())->method('toArray')->willReturn([1, 2, 3]);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame([1, 2, 3], $tokensAdapter->toArray());
    }

    public function testTokens(): void
    {
        $tokens = $this->createMock(Tokens::class);
        $tokens->method('count')->willReturn(0);

        $tokensAdapter = new TokensAdapter($tokens);

        static::assertSame($tokens, $tokensAdapter->tokens());
    }
}
