<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocNoSuperfluousParamFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no superfluous parameters in PHPDoc.',
            [new CodeSample('<?php
/**
 * @param bool $b
 * @param int $i
 * @param string $s this is string
 * @param string $s duplicated
 */
function foo($b, $s) {}
')]
        );
    }

    public function getPriority(): int
    {
        // must be run after CommentToPhpdocFixer
        // must be run before NoEmptyPhpdocFixer
        return 6;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([T_DOC_COMMENT, T_FUNCTION]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0; $index < $tokens->count(); $index++) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $functionIndex = $tokens->getTokenNotOfKindSibling($index, 1, [[T_ABSTRACT], [T_COMMENT], [T_FINAL], [T_PRIVATE], [T_PROTECTED], [T_PUBLIC], [T_STATIC], [T_WHITESPACE]]);

            if ($functionIndex === null) {
                return;
            }

            /** @var Token $functionToken */
            $functionToken = $tokens[$functionIndex];

            if (!$functionToken->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $paramNames = $this->getParamNames($tokens, $functionIndex);

            $newContent = $this->getFilteredDocComment($token->getContent(), $paramNames);

            if ($newContent === $token->getContent()) {
                continue;
            }

            if ($newContent === '') {
                $tokens->clearTokenAndMergeSurroundingWhitespace($index);
            } else {
                $tokens[$index] = new Token([T_DOC_COMMENT, $newContent]);
            }
        }
    }

    /**
     * @return string[]
     */
    private function getParamNames(Tokens $tokens, int $functionIndex): array
    {
        /** @var int $paramBlockStartIndex */
        $paramBlockStartIndex = $tokens->getNextTokenOfKind($functionIndex, ['(']);

        $paramBlockEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $paramBlockStartIndex);

        $paramNames = [];
        for ($index = $paramBlockStartIndex; $index < $paramBlockEndIndex; $index++) {
            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->isGivenKind(T_VARIABLE)) {
                $paramNames[] = $token->getContent();
            }
        }

        return $paramNames;
    }

    /**
     * @param string[] $paramNames
     */
    private function getFilteredDocComment(string $comment, array $paramNames): string
    {
        $regexParamNamesPattern = '(\Q' . \implode('\E|\Q', $paramNames) . '\E)';

        $doc = new DocBlock($comment);
        $foundParamNames = [];

        foreach ($doc->getAnnotationsOfType('param') as $annotation) {
            if (Preg::match(\sprintf('/@param\s+(?:[^\$](?:[^<\s]|<[^>]*>)*\s+)?(?:&|\.\.\.)?\s*(?=\$)%s\b/', $regexParamNamesPattern), $annotation->getContent(), $matches) === 1 && !isset($foundParamNames[$matches[1]])) {
                $foundParamNames[$matches[1]] = true;
                continue;
            }

            $annotation->remove();
        }

        return $doc->getContent();
    }
}
