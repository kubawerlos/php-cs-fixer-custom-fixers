<?php

declare(strict_types = 1);

namespace Tests;

use PhpCsFixer\Tests\Test\Assert\AssertTokensTrait;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\TokenRemover;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\TokenRemover
 */
final class TokenRemoverTest extends TestCase
{
    use AssertTokensTrait;

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $tokens = Tokens::fromCode($input);

        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind([T_COMMENT, T_DOC_COMMENT])) {
                TokenRemover::removeWithLinesIfPossible($tokens, $index);
                break;
            }
        }

        $tokens->clearEmptyTokens();

        static::assertTokens(Tokens::fromCode($expected), $tokens);
    }

    public function provideFixCases(): iterable
    {
        yield 'one-line comment after open tag' => [
            '<?php
namespace Foo;
',
            '<?php
// Some comment
namespace Foo;
',
        ];

        yield 'PHPDoc after open tag' => [
            '<?php
namespace Foo;
',
            '<?php
/**
 * Some comment
 */
namespace Foo;
',
        ];

        yield 'single empty line before PHPDoc' => [
            '<?php

namespace Foo;
',
            '<?php

/**
 * Some comment
 */
namespace Foo;
',
        ];

        yield 'multiple empty lines before PHPDoc' => [
            '<?php


namespace Foo;
',
            '<?php


/**
 * Some comment
 */
namespace Foo;
',
        ];

        yield 'single empty line after PHPDoc' => [
            '<?php

namespace Foo;
',
            '<?php
/**
 * Some comment
 */

namespace Foo;
',
        ];

        yield 'multiple empty lines after PHPDoc' => [
            '<?php


namespace Foo;
',
            '<?php
/**
 * Some comment
 */


namespace Foo;
',
        ];

        yield 'indented one-line comment' => [
            '<?php


    namespace Foo;
',
            '<?php

    // Some comment

    namespace Foo;
',
        ];

        yield 'indented PHPDoc' => [
            '<?php


    namespace Foo;
',
            '<?php

    /**
     * Some comment
     */

    namespace Foo;
',
        ];

        yield 'indented after open tag' => [
            '<?php
                namespace Foo;
',
            '<?php
                /**
                 * Some comment
                 */
                namespace Foo;
',
        ];

        yield 'code after PHPDoc' => [
            '<?php
namespace Foo;
',
            '<?php
/** Some comment */namespace Foo;
',
        ];

        yield 'spaces and code after PHPDoc' => [
            '<?php
    namespace Foo;
',
            '<?php
/** Some comment */    namespace Foo;
',
        ];

        yield 'code and space before comment' => [
            '<?php 
foo();
',
            '<?php // Foo
foo();
',
        ];

        yield 'comment after open tag with only spaces' => [
            '<?php    
foo();
',
            '<?php    // Foo
foo();
',
        ];

        yield 'code before comment' => [
            '<?php
            foo();
            bar();
            ',
            '<?php
            foo();// Foo
            bar();
            ',
        ];

        yield 'comment as last token' => [
            '<?php
',
            '<?php

// Some comment',
        ];

        yield 'comment with newlines after token' => [
            '<?php
/* 
    second comment */
',
            '<?php
/* first comment *//* 
    second comment */
',
        ];
    }
}
