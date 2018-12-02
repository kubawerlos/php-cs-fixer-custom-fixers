<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\CommentsAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\TokenRemover;

final class NoCommentedOutCodeFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinition
    {
        return new FixerDefinition(
            'There should be no commented out code.',
            [new CodeSample("<?php\n//var_dump(\$_POST);\nprint_r(\$_POST);\n")]
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_COMMENT, T_DOC_COMMENT]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function getPriority(): int
    {
        // must be run before NoUnusedImportsFixer
        return 0;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $commentsAnalyzer = new CommentsAnalyzer();

        for ($index = 0; $index < $tokens->count(); $index++) {
            if (!$tokens[$index]->isGivenKind([T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }

            if (\strpos($tokens[$index]->getContent(), '/*') === 0) {
                $commentIndices = [$index];
            } else {
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

    private function getIndicesToRemove(Tokens $tokens, array $commentIndices): array
    {
        $indicesToRemove = [];
        $testedIndices = [];
        $content = '<?php ';

        foreach ($commentIndices as $index) {
            $newContent = $content . PHP_EOL . $this->getMessage($tokens[$index]->getContent());
            $testedIndices[] = $index;

            if (\strlen(\rtrim($newContent)) === 5) {
                continue;
            }

            try {
                @Tokens::fromCode($newContent);
            } catch (\ParseError $error) {
                $content = $newContent;
                continue;
            }

            $indicesToRemove = $testedIndices;
            $content = $newContent;
        }

        return $indicesToRemove;
    }

    private function getMessage(string $content): string
    {
        if (\strpos($content, '#') === 0) {
            return \substr($content, 1);
        }
        if (\strpos($content, '//') === 0) {
            return \substr($content, 2);
        }

        return Preg::replace('/(^\/\*+|\R\h*\**\h*)(.*)((?=\R)|\*+\/$)/', '$2', $content);
    }
}
