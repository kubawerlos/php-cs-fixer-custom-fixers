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

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\CommentsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\TokenRemover;

final class NoCommentedOutCodeFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There should be no commented out code.',
            [new CodeSample("<?php\n//var_dump(\$_POST);\nprint_r(\$_POST);\n")]
        );
    }

    public function getPriority(): int
    {
        // must be run after CommentedOutFunctionFixer
        // must be run before NoExtraBlankLinesFixer and NoUnusedImportsFixer
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_COMMENT, T_DOC_COMMENT]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $commentsAnalyzer = new CommentsAnalyzer();

        $index = 0;
        while (++$index < $tokens->count()) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->isGivenKind([T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }

            if (\strpos($token->getContent(), '/*') === 0) {
                $commentIndices = [$index];
            } else {
                /** @var int[] $commentIndices */
                $commentIndices = $commentsAnalyzer->getCommentBlockIndices($tokens, $index);
            }

            $indicesToRemove = $this->getIndicesToRemove($tokens, $commentIndices);

            if ($indicesToRemove === []) {
                continue;
            }

            foreach ($indicesToRemove as $indexToRemove) {
                TokenRemover::removeWithLinesIfPossible($tokens, $indexToRemove);
            }

            $index = \max($indicesToRemove);
        }
    }

    /**
     * @param int[] $commentIndices
     *
     * @return int[]
     */
    private function getIndicesToRemove(Tokens $tokens, array $commentIndices): array
    {
        $indicesToRemove = [];
        $testedIndices = [];
        $content = '';

        foreach ($commentIndices as $index) {
            /** @var Token $token */
            $token = $tokens[$index];

            $content .= PHP_EOL . $this->getContent($token);
            $testedIndices[] = $index;

            if (\rtrim($content) === '') {
                continue;
            }

            if ($this->isCorrectSyntax('<?php' . $content)
                || $this->isCorrectSyntax('<?php class Foo {' . $content . PHP_EOL . '}')) {
                $indicesToRemove = $testedIndices;
            }
        }

        return $indicesToRemove;
    }

    private function getContent(Token $token): string
    {
        $content = $token->getContent();

        if (\strpos($content, '/*') === 0) {
            /** @var string $content */
            $content = Preg::replace('~^/\*+|\R\s*\*\s+|\*+/$~', PHP_EOL, $content);
        }

        return \ltrim($content, '#/');
    }

    private function isCorrectSyntax(string $content): bool
    {
        try {
            @Tokens::fromCode($content);
        } catch (\ParseError $error) {
            return false;
        }

        return true;
    }
}
