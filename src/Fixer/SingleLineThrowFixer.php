<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class SingleLineThrowFixer extends AbstractFixer implements DeprecatingFixerInterface
{
    private const REMOVE_WHITESPACE_AFTER_TOKENS = ['['];
    private const REMOVE_WHITESPACE_AROUND_TOKENS = ['.', '(', [T_OBJECT_OPERATOR], [T_DOUBLE_COLON]];
    private const REMOVE_WHITESPACE_BEFORE_TOKENS = [')',  ']', ',', ';'];

    public function getDefinition(): FixerDefinition
    {
        return new FixerDefinition(
            '`throw` must be single line.',
            [
                new CodeSample("<?php\nthrow new Exception(\n    'Error',\n    500\n);\n"),
            ]
        );
    }

    public function getPullRequestId(): int
    {
        return 4452;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_THROW);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0, $count = $tokens->count(); $index < $count; $index++) {
            if (!$tokens[$index]->isGivenKind(T_THROW)) {
                continue;
            }

            /** @var int $openingBraceCandidateIndex */
            $openingBraceCandidateIndex = $tokens->getNextTokenOfKind($index, [';', '(']);

            while ($tokens[$openingBraceCandidateIndex]->equals('(')) {
                $closingBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openingBraceCandidateIndex);
                /** @var int $openingBraceCandidateIndex */
                $openingBraceCandidateIndex = $tokens->getNextTokenOfKind($closingBraceIndex, [';', '(']);
            }

            $this->trimNewLines($tokens, $index, $openingBraceCandidateIndex);
        }
    }

    public function getPriority(): int
    {
        // must be fun before ConcatSpaceFixer and NoUnneededConcatenationFixer
        return 1;
    }

    /**
     * @param Tokens $tokens
     * @param int    $startIndex
     * @param int    $endIndex
     */
    private function trimNewLines(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (!$tokens[$index]->isGivenKind(T_WHITESPACE)) {
                continue;
            }

            if (Preg::match('/\R/', $tokens[$index]->getContent()) === 0) {
                continue;
            }

            $prevIndex = $tokens->getNonEmptySibling($index, -1);
            if ($tokens[$prevIndex]->equalsAny(\array_merge(self::REMOVE_WHITESPACE_AFTER_TOKENS, self::REMOVE_WHITESPACE_AROUND_TOKENS))) {
                $tokens->clearAt($index);
                continue;
            }

            $nextIndex = $tokens->getNonEmptySibling($index, 1);
            if ($tokens[$nextIndex]->equalsAny(\array_merge(self::REMOVE_WHITESPACE_AROUND_TOKENS, self::REMOVE_WHITESPACE_BEFORE_TOKENS))) {
                if (!$tokens[$prevIndex]->isGivenKind(T_FUNCTION)) {
                    $tokens->clearAt($index);
                    continue;
                }
            }

            $tokens[$index] = new Token([T_WHITESPACE, ' ']);
        }
    }
}
