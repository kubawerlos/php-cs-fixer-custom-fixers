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
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Utils;
use PhpCsFixerCustomFixers\TokenRemover;

final class PhpUnitNoUselessReturnFixer extends AbstractFixer
{
    private const FUNCTION_TOKENS = [[T_STRING, 'fail'], [T_STRING, 'markTestIncomplete'], [T_STRING, 'markTestSkipped']];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            \sprintf(
                "PHPUnit's functions %s should not be followed directly by return.",
                Utils::naturalLanguageJoinWithBackticks(\array_map(
                    static function (array $token): string {
                        return $token[1];
                    },
                    self::FUNCTION_TOKENS
                ))
            ),
            [new CodeSample('<?php
class FooTest extends TestCase {
    public function testFoo() {
        $this->markTestSkipped();
        return;
    }
}
')],
            'They will throw exception anyway.',
            "when PHPUnit's native methods are overridden"
        );
    }

    public function getPriority(): int
    {
        // must be run before NoExtraBlankLinesFixer
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([T_CLASS, T_EXTENDS, T_FUNCTION, T_STRING, T_RETURN]);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();

        /** @var int[] $indexes */
        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indexes) {
            $this->removeUselessReturns($tokens, $indexes[0], $indexes[1]);
        }
    }

    private function removeUselessReturns(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        $functionsAnalyzer = new FunctionsAnalyzer();

        for ($index = $startIndex; $index < $endIndex; $index++) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->equalsAny(self::FUNCTION_TOKENS, false)) {
                continue;
            }

            /** @var int $openingBraceIndex */
            $openingBraceIndex = $tokens->getNextMeaningfulToken($index);

            /** @var Token $openingBraceToken */
            $openingBraceToken = $tokens[$openingBraceIndex];

            if (!$openingBraceToken->equals('(')) {
                continue;
            }

            if (!$functionsAnalyzer->isTheSameClassCall($tokens, $index)) {
                continue;
            }

            $closingBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openingBraceIndex);

            /** @var int $semicolonIndex */
            $semicolonIndex = $tokens->getNextMeaningfulToken($closingBraceIndex);

            /** @var int $returnIndex */
            $returnIndex = $tokens->getNextMeaningfulToken($semicolonIndex);

            /** @var Token $returnToken */
            $returnToken = $tokens[$returnIndex];

            if (!$returnToken->isGivenKind(T_RETURN)) {
                continue;
            }

            /** @var int $semicolonAfterReturnIndex */
            $semicolonAfterReturnIndex = $tokens->getNextTokenOfKind($returnIndex, [';', '(']);

            /** @var Token $semicolonAfterReturnToken */
            $semicolonAfterReturnToken = $tokens[$semicolonAfterReturnIndex];

            while ($semicolonAfterReturnToken->equals('(')) {
                $closingBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $semicolonAfterReturnIndex);

                /** @var int $semicolonAfterReturnIndex */
                $semicolonAfterReturnIndex = $tokens->getNextTokenOfKind($closingBraceIndex, [';', '(']);

                /** @var Token $semicolonAfterReturnToken */
                $semicolonAfterReturnToken = $tokens[$semicolonAfterReturnIndex];
            }

            $tokens->clearRange($returnIndex, $semicolonAfterReturnIndex - 1);
            TokenRemover::removeWithLinesIfPossible($tokens, $semicolonAfterReturnIndex);
        }
    }
}
