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

            $startIndex = $index;

            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($index);

            /** @var Token $prevToken */
            $prevToken = $tokens[$prevIndex];

            if ($prevToken->isGivenKind(T_NS_SEPARATOR)) {
                $startIndex = $prevIndex;
            }

            if (!$this->isPreviousTokenSeparateStatement($tokens, $startIndex)) {
                continue;
            }

            /** @var int $indexParenthesisStart */
            $indexParenthesisStart = $tokens->getNextMeaningfulToken($index);

            $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $indexParenthesisStart);

            /** @var int $semicolonIndex */
            $semicolonIndex = $tokens->getNextMeaningfulToken($endIndex);

            /** @var Token $semicolonToken */
            $semicolonToken = $tokens[$semicolonIndex];

            if (!$semicolonToken->equalsAny([';', [T_CLOSE_TAG]])) {
                continue;
            }

            if ($semicolonToken->equals(';')) {
                $endIndex = $semicolonIndex;
            }

            $this->fixBlock($tokens, $startIndex, $endIndex);
        }
    }

    private function isFunctionToFix(Tokens $tokens, int $index): bool
    {
        /** @var Token $token */
        $token = $tokens[$index];

        if (!$token->isGivenKind(T_STRING)) {
            return false;
        }

        if (!\in_array(\strtolower($token->getContent()), $this->functions, true)) {
            return false;
        }

        return (new FunctionsAnalyzer())->isGlobalFunctionCall($tokens, $index);
    }

    private function isPreviousTokenSeparateStatement(Tokens $tokens, int $index): bool
    {
        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevMeaningfulToken($index);

        /** @var Token $prevToken */
        $prevToken = $tokens[$prevIndex];

        if ($prevToken->equalsAny([';', '{', '}', [T_OPEN_TAG]])) {
            return true;
        }

        $switchAnalyzer = new SwitchAnalyzer();

        if (!$prevToken->equals(':')) { // can be part of ternary operator or from switch/case
            return false;
        }

        for ($i = $index; $i > 0; $i--) {
            /** @var Token $token */
            $token = $tokens[$i];

            if (!$token->isGivenKind(T_SWITCH)) {
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

    private function fixBlock(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        if ($this->canUseSingleLineComment($tokens, $startIndex, $endIndex)) {
            $this->fixBlockWithSingleLineComments($tokens, $startIndex, $endIndex);

            return;
        }

        $tokens->overrideRange(
            $startIndex,
            $endIndex,
            [new Token([T_COMMENT, '/*' . $tokens->generatePartialCode($startIndex, $endIndex) . '*/'])]
        );
    }

    private function canUseSingleLineComment(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        if (!$tokens->offsetExists($endIndex + 1)) {
            return true;
        }

        /** @var Token $afterEndToken */
        $afterEndToken = $tokens[$endIndex + 1];

        if (Preg::match('/^\R/', $afterEndToken->getContent()) === 1) {
            return true;
        }

        for ($index = $startIndex; $index < $endIndex; $index++) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (\strpos($token->getContent(), '*/') !== false) {
                return true;
            }
        }

        return false;
    }

    private function fixBlockWithSingleLineComments(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        $codeToCommentOut = $tokens->generatePartialCode($startIndex, $endIndex);

        /** @var Token $beforeStartToken */
        $beforeStartToken = $tokens[$startIndex - 1];

        $prefix = '//';
        if ($beforeStartToken->isWhitespace()) {
            $startIndex--;
            /** @var string $prefix */
            $prefix = Preg::replace('/(^|\R)(\h*$)/D', '$1//$2', $beforeStartToken->getContent());
        }
        $codeToCommentOut = $prefix . \str_replace("\n", "\n//", $codeToCommentOut);

        if ($tokens->offsetExists($endIndex + 1)) {
            /** @var Token $afterEndToken */
            $afterEndToken = $tokens[$endIndex + 1];

            if (Preg::match('/^\R/', $afterEndToken->getContent()) === 0) {
                $codeToCommentOut .= "\n";
                if ($afterEndToken->isWhitespace()) {
                    $endIndex++;
                    $codeToCommentOut .= $afterEndToken->getContent();
                }
            }
        }

        $newTokens = Tokens::fromCode('<?php ' . $codeToCommentOut);
        $newTokens->clearAt(0);
        $newTokens->clearEmptyTokens();

        $tokens->overrideRange(
            $startIndex,
            $endIndex,
            $newTokens
        );
    }
}
