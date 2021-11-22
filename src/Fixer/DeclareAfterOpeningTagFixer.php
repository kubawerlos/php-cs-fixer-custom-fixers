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
use PhpCsFixerCustomFixers\TokenRemover;

final class DeclareAfterOpeningTagFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Declare statement must be placed in the same line, after opening tag.',
            [new CodeSample("<?php\n\$foo;\ndeclare(strict_types=1);\n\$bar;\n")]
        );
    }

    /**
     * Must run after BlankLineAfterOpeningTagFixer, HeaderCommentFixer.
     */
    public function getPriority(): int
    {
        return -31;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_DECLARE);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        if (!$tokens[0]->isGivenKind(\T_OPEN_TAG)) {
            return;
        }

        $openingTagTokenContent = $tokens[0]->getContent();

        $tokens[0] = new Token([\T_OPEN_TAG, \substr($openingTagTokenContent, 0, 5) . ' ']);

        /** @var int $declareIndex */
        $declareIndex = $tokens->getNextTokenOfKind(0, [[\T_DECLARE]]);
        if ($declareIndex <= 2) {
            $tokens->clearRange(1, $declareIndex - 1);

            return;
        }

        $tokensToInsert = [];
        if ($tokens[1]->isGivenKind(\T_WHITESPACE)) {
            $tokens[1] = new Token([\T_WHITESPACE, \substr($openingTagTokenContent, 5) . $tokens[1]->getContent()]);
        } else {
            $tokens->insertAt(1, new Token([\T_WHITESPACE, \substr($openingTagTokenContent, 5)]));
            $declareIndex++;
        }

        /** @var int $openParenthesisIndex */
        $openParenthesisIndex = $tokens->getNextMeaningfulToken($declareIndex);
        $closeParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesisIndex);

        /** @var int $semicolonIndex */
        $semicolonIndex = $tokens->getNextMeaningfulToken($closeParenthesisIndex);

        for ($index = $declareIndex; $index <= $semicolonIndex; $index++) {
            $tokensToInsert[] = $tokens[$index];
        }

        if ($tokens[$semicolonIndex + 1]->isGivenKind(\T_WHITESPACE)) {
            /** @var string $content */
            $content = Preg::replace('/^(\R?)(?=\R)/', '', $tokens[$semicolonIndex + 1]->getContent());

            $tokens->ensureWhitespaceAtIndex($semicolonIndex + 1, 0, $content);
        }

        $tokens->clearRange($declareIndex + 1, $semicolonIndex);
        TokenRemover::removeWithLinesIfPossible($tokens, $declareIndex);

        $tokens->insertAt(1, $tokensToInsert);
    }
}
