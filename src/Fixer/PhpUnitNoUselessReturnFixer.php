<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
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
        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (!$tokens[$index]->equalsAny(self::FUNCTION_TOKENS, false)) {
                continue;
            }

            /** @var int $openingBraceIndex */
            $openingBraceIndex = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$openingBraceIndex]->equals('(')) {
                continue;
            }

            if (!$this->isTheSameClassCall($tokens, $index)) {
                continue;
            }

            $closingBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openingBraceIndex);

            /** @var int $semicolonIndex */
            $semicolonIndex = $tokens->getNextMeaningfulToken($closingBraceIndex);

            /** @var int $returnIndex */
            $returnIndex = $tokens->getNextMeaningfulToken($semicolonIndex);
            if (!$tokens[$returnIndex]->isGivenKind(T_RETURN)) {
                continue;
            }

            /** @var int $semicolonAfterReturnIndex */
            $semicolonAfterReturnIndex = $tokens->getNextTokenOfKind($returnIndex, [';', '(']);

            while ($tokens[$semicolonAfterReturnIndex]->equals('(')) {
                $closingBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $semicolonAfterReturnIndex);
                /** @var int $semicolonAfterReturnIndex */
                $semicolonAfterReturnIndex = $tokens->getNextTokenOfKind($closingBraceIndex, [';', '(']);
            }

            $tokens->clearRange($returnIndex, $semicolonAfterReturnIndex - 1);
            TokenRemover::removeWithLinesIfPossible($tokens, $semicolonAfterReturnIndex);
        }
    }

    private function isTheSameClassCall(Tokens $tokens, int $index): bool
    {
        /** @var int $operatorIndex */
        $operatorIndex = $tokens->getPrevMeaningfulToken($index);

        /** @var int $referenceIndex */
        $referenceIndex = $tokens->getPrevMeaningfulToken($operatorIndex);

        return $tokens[$operatorIndex]->isGivenKind(T_OBJECT_OPERATOR) && $tokens[$referenceIndex]->equals([T_VARIABLE, '$this'], false)
            || $tokens[$operatorIndex]->isGivenKind(T_DOUBLE_COLON) && $tokens[$referenceIndex]->equals([T_STRING, 'self'], false)
            || $tokens[$operatorIndex]->isGivenKind(T_DOUBLE_COLON) && $tokens[$referenceIndex]->isGivenKind(T_STATIC);
    }
}
