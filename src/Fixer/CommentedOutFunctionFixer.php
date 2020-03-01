<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\SwitchAnalyzer;

final class CommentedOutFunctionFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /** @var string[] */
    private $functions = ['print_r', 'var_dump', 'var_export'];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Configured functions must be commented out.',
            [new CodeSample('<?php
var_dump($x);
')],
            null,
            'when any of the configured functions has side effects or is overridden'
        );
    }

    public function getConfigurationDefinition(): FixerConfigurationResolver
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('functions', 'list of functions to comment out'))
                ->setDefault($this->functions)
                ->setAllowedTypes(['array'])
                ->getOption(),
        ]);
    }

    public function configure(?array $configuration = null): void
    {
        if (isset($configuration['functions'])) {
            /** @var string[] $elements */
            $elements = $configuration['functions'];
            $this->functions = $elements;
        }
    }

    public function getPriority(): int
    {
        // must be run before CommentSurroundedBySpacesFixer and NoCommentedOutCodeFixer
        return 2;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$this->isFunctionToFix($tokens, $index)) {
                continue;
            }

            $indexStart = $index;

            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            if ($tokens[$prevIndex]->isGivenKind(T_NS_SEPARATOR)) {
                $indexStart = $prevIndex;
            }

            if (!$this->isPreviousTokenSeparateStatement($tokens, $indexStart)) {
                continue;
            }

            /** @var int $indexParenthesisStart */
            $indexParenthesisStart = $tokens->getNextMeaningfulToken($index);

            $indexEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $indexParenthesisStart);

            /** @var int $indexSemicolon */
            $indexSemicolon = $tokens->getNextMeaningfulToken($indexEnd);
            if (!$tokens[$indexSemicolon]->equalsAny([';', [T_CLOSE_TAG]])) {
                continue;
            }
            if ($tokens[$indexSemicolon]->equals(';')) {
                $indexEnd = $indexSemicolon;
            }

            $this->fixBlock($tokens, $indexStart, $indexEnd);
        }
    }

    private function isFunctionToFix(Tokens $tokens, int $index): bool
    {
        if (!$tokens[$index]->isGivenKind(T_STRING)) {
            return false;
        }

        if (!\in_array(\strtolower($tokens[$index]->getContent()), $this->functions, true)) {
            return false;
        }

        return (new FunctionsAnalyzer())->isGlobalFunctionCall($tokens, $index);
    }

    private function isPreviousTokenSeparateStatement(Tokens $tokens, int $index): bool
    {
        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevMeaningfulToken($index);

        if ($tokens[$prevIndex]->equalsAny([';', '{', '}', [T_OPEN_TAG]])) {
            return true;
        }

        $switchAnalyzer = new SwitchAnalyzer();

        if (!$tokens[$prevIndex]->equals(':')) { // can be part of ternary operator or from switch/case
            return false;
        }

        for ($i = $index; $i > 0; $i--) {
            if (!$tokens[$i]->isGivenKind(T_SWITCH)) {
                continue;
            }
            foreach ($switchAnalyzer->getSwitchAnalysis($tokens, $i)->getCases() as $caseAnalysis) {
                if ($caseAnalysis->getColonIndex() === $prevIndex) {
                    return true;
                }
            }
        }

        return false;
    }

    private function fixBlock(Tokens $tokens, int $indexStart, int $indexEnd): void
    {
        if ($this->canUseSingleLineComment($tokens, $indexStart, $indexEnd)) {
            $this->fixBlockWithSingleLineComments($tokens, $indexStart, $indexEnd);

            return;
        }

        $tokens->overrideRange(
            $indexStart,
            $indexEnd,
            [new Token([T_COMMENT, '/*' . $tokens->generatePartialCode($indexStart, $indexEnd) . '*/'])]
        );
    }

    private function canUseSingleLineComment(Tokens $tokens, int $indexStart, int $indexEnd): bool
    {
        if (!$tokens->offsetExists($indexEnd + 1)) {
            return true;
        }

        if (Preg::match('/^\R/', $tokens[$indexEnd + 1]->getContent()) === 1) {
            return true;
        }

        for ($index = $indexStart; $index < $indexEnd; $index++) {
            if (\strpos($tokens[$index]->getContent(), '*/') !== false) {
                return true;
            }
        }

        return false;
    }

    private function fixBlockWithSingleLineComments(Tokens $tokens, int $indexStart, int $indexEnd): void
    {
        $codeToCommentOut = $tokens->generatePartialCode($indexStart, $indexEnd);

        $prefix = '//';
        if ($tokens[$indexStart - 1]->isWhitespace()) {
            $indexStart--;
            /** @var string $prefix */
            $prefix = Preg::replace('/(^|\R)(\h*$)/D', '$1//$2', $tokens[$indexStart]->getContent());
        }
        $codeToCommentOut = $prefix . \str_replace("\n", "\n//", $codeToCommentOut);

        if ($tokens->offsetExists($indexEnd + 1) && Preg::match('/^\R/', $tokens[$indexEnd + 1]->getContent()) === 0) {
            $codeToCommentOut .= "\n";
            if ($tokens[$indexEnd + 1]->isWhitespace()) {
                $indexEnd++;
                $codeToCommentOut .= $tokens[$indexEnd]->getContent();
            }
        }

        $newTokens = Tokens::fromCode('<?php ' . $codeToCommentOut);
        $newTokens->clearAt(0);
        $newTokens->clearEmptyTokens();

        $tokens->overrideRange(
            $indexStart,
            $indexEnd,
            $newTokens
        );
    }
}
