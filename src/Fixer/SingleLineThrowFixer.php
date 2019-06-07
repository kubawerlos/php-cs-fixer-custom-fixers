<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class SingleLineThrowFixer extends AbstractFixer
{
    private const REMOVE_WHITESPACE_AROUND_TOKENS = ['(', ')', [T_OBJECT_OPERATOR], [T_DOUBLE_COLON]];
    private const REMOVE_WHITESPACE_BEFORE_TOKENS = [','];

    public function getDefinition(): FixerDefinition
    {
        return new FixerDefinition(
            '`throw` must be single line.',
            [
                new CodeSample("<?php\nthrow new Exception(\n    'Error',\n    500\n);\n"),
            ]
        );
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

            /** @var int $openBraceCandidateIndex */
            $openBraceCandidateIndex = $tokens->getNextTokenOfKind($index, [';', '(']);
            if (!$tokens[$openBraceCandidateIndex]->equals('(')) {
                continue;
            }

            $this->trimNewLines($tokens, $index, $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openBraceCandidateIndex));
        }
    }

    public function getPriority(): int
    {
        // must be fun before ConcatSpaceFixer and MethodArgumentSpaceFixer
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
            if ($tokens[$prevIndex]->equalsAny(self::REMOVE_WHITESPACE_AROUND_TOKENS)) {
                $tokens->clearAt($index);
                continue;
            }

            $nextIndex = $tokens->getNonEmptySibling($index, 1);
            if ($tokens[$nextIndex]->equalsAny(\array_merge(self::REMOVE_WHITESPACE_AROUND_TOKENS, self::REMOVE_WHITESPACE_BEFORE_TOKENS))) {
                $tokens->clearAt($index);
                continue;
            }

            $tokens[$index] = new Token([T_WHITESPACE, ' ']);
        }
    }
}
