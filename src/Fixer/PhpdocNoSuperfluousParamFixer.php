<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocNoSuperfluousParamFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinition
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
            if (!$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $functionIndex = $tokens->getTokenNotOfKindSibling($index, 1, [[T_COMMENT], [T_PRIVATE], [T_PROTECTED], [T_PUBLIC], [T_STATIC], [T_WHITESPACE]]);
            if ($functionIndex === null || !$tokens[$functionIndex]->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $paramNames = $this->getParamNames($tokens, $functionIndex);

            $newContent = $this->getFilteredDocComment($tokens[$index]->getContent(), $paramNames);

            if ($newContent === $tokens[$index]->getContent()) {
                continue;
            }

            if ($newContent === '') {
                $tokens->clearTokenAndMergeSurroundingWhitespace($index);
            } else {
                $tokens[$index] = new Token([T_DOC_COMMENT, $newContent]);
            }
        }
    }

    public function getPriority(): int
    {
        return 6;
    }

    /**
     * @return string[]
     */
    private function getParamNames(Tokens $tokens, int $functionIndex): array
    {
        $paramBlockStartIndex = $tokens->getNextTokenOfKind($functionIndex, ['(']);
        $paramBlockEndIndex   = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $paramBlockStartIndex);

        $paramNames = [];
        for ($index = $paramBlockStartIndex; $index < $paramBlockEndIndex; $index++) {
            if ($tokens[$index]->isGivenKind(T_VARIABLE)) {
                $paramNames[] = $tokens[$index]->getContent();
            }
        }

        return \array_unique($paramNames);
    }

    private function getFilteredDocComment(string $comment, array $paramNames): string
    {
        $doc             = new DocBlock($comment);
        $foundParamNames = [];

        foreach ($doc->getAnnotationsOfType('param') as $annotation) {
            $regexParamNamesPattern = \implode('|', \array_map(static function (string $paramName): string {
                return \preg_quote($paramName, '/');
            }, $paramNames));

            if (\preg_match(\sprintf('/@param\s+(?:[^\$](?:[^<\s]|<[^>]*>)*\s+)?(?:&|\.\.\.)?\s*(%s)\b/u', $regexParamNamesPattern), $annotation->getContent(), $matches) === 1 && !isset($foundParamNames[$matches[1]])) {
                $foundParamNames[$matches[1]] = true;
                continue;
            }
            $annotation->remove();
        }

        return $doc->getContent();
    }
}
