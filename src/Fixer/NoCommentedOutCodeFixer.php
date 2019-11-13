<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\CommentsAnalyzer;
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
        // must be run before NoUnusedImportsFixer
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

        for ($index = 0; $index < $tokens->count(); $index++) {
            if (!$tokens[$index]->isGivenKind([T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }

            if (\strpos($tokens[$index]->getContent(), '/*') === 0) {
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
            $content .= PHP_EOL . $this->getContent($tokens[$index]->getContent());
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

    private function getContent(string $content): string
    {
        /** @var string $content */
        $content = Preg::replace('~^/\*+|\R\s*\*\s+|\*+/$~', PHP_EOL, $content);

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
