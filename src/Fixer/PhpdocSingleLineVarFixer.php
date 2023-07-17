<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocSingleLineVarFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'The `@var` annotations must be on a single line if they are the only content.',
            [new CodeSample('<?php
class Foo {
    /**
     * @var string
     */
    private $name;
}
')],
            '',
        );
    }

    /**
     * Must run after PhpdocLineSpanFixer.
     */
    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_DOC_COMMENT);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind(\T_DOC_COMMENT)) {
                continue;
            }

            if (!Preg::match('#^/\*\*[\s\*]+(@var[^\r\n]+)[\s\*]*\*\/$#u', $tokens[$index]->getContent(), $matches)) {
                continue;
            }

            $var = $matches[1];
            \assert(\is_string($var));

            $newContent = '/** ' . \rtrim($var) . ' */';

            if ($newContent === $tokens[$index]->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([\T_DOC_COMMENT, $newContent]);
        }
    }
}
