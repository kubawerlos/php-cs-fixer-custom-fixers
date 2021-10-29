<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ArgumentAnalysis;
use PhpCsFixerCustomFixers\Analyzer\FunctionAnalyzer;

final class PhpUnitDedicatedAssertFixer extends AbstractFixer
{
    private const ASSERTIONS = [
        'assertEquals',
        'assertNotEquals',
        'assertSame',
        'assertNotSame',
    ];
    private const REPLACEMENTS_MAP = [
        'count' => [
            'positive' => 'assertCount',
            'negative' => 'assertNotCount',
        ],
        'get_class' => [
            'positive' => 'assertInstanceOf',
            'negative' => 'assertNotInstanceOf',
        ],
        'sizeof' => [
            'positive' => 'assertCount',
            'negative' => 'assertNotCount',
        ],
    ];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'PHPUnit assertions like `assertCount` and `assertInstanceOf` must be used over `assertEquals`/`assertSame`.',
            [new CodeSample('<?php
class FooTest extends TestCase {
    public function testFoo() {
        self::assertSame($size, count($elements));
        self::assertSame($className, get_class($object));
    }
}
')],
            null,
            'when original PHPUnit methods are overwritten'
        );
    }

    /**
     * Must run before NoUnusedImportsFixer.
     * Must run after PhpUnitAssertArgumentsOrderFixer.
     */
    public function getPriority(): int
    {
        return -1;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([\T_CLASS, \T_EXTENDS, \T_FUNCTION]);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();

        /** @var array<int> $indexes */
        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indexes) {
            $this->fixAssertions($tokens, $indexes[0], $indexes[1]);
        }
    }

    private function fixAssertions(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (!self::isAssertionCall($tokens, $index)) {
                continue;
            }

            $arguments = FunctionAnalyzer::getFunctionArguments($tokens, $index);
            if (\count($arguments) < 2) {
                continue;
            }

            self::fixAssertion($tokens, $index, $arguments[1]);
        }
    }

    private static function isAssertionCall(Tokens $tokens, int $index): bool
    {
        static $assertions;

        if ($assertions === null) {
            $assertions = \array_flip(
                \array_map(
                    static function (string $name): string {
                        return \strtolower($name);
                    },
                    self::ASSERTIONS
                )
            );
        }

        if (!isset($assertions[\strtolower($tokens[$index]->getContent())])) {
            return false;
        }

        /** @var int $openingBraceIndex */
        $openingBraceIndex = $tokens->getNextMeaningfulToken($index);

        if (!$tokens[$openingBraceIndex]->equals('(')) {
            return false;
        }

        return (new FunctionsAnalyzer())->isTheSameClassCall($tokens, $index);
    }

    private static function fixAssertion(Tokens $tokens, int $assertionIndex, ArgumentAnalysis $secondArgument): void
    {
        $functionCallIndex = $secondArgument->getStartIndex();
        if ($tokens[$functionCallIndex]->isGivenKind(\T_NS_SEPARATOR)) {
            /** @var int $functionCallIndex */
            $functionCallIndex = $tokens->getNextMeaningfulToken($functionCallIndex);
        }

        if (!(new FunctionsAnalyzer())->isGlobalFunctionCall($tokens, $functionCallIndex)) {
            return;
        }

        $arguments = FunctionAnalyzer::getFunctionArguments($tokens, $functionCallIndex);
        if (\count($arguments) !== 1) {
            return;
        }

        $functionName = \strtolower($tokens[$functionCallIndex]->getContent());

        if (!isset(self::REPLACEMENTS_MAP[$functionName])) {
            return;
        }

        $newAssertion = self::REPLACEMENTS_MAP[$functionName][\stripos($tokens[$assertionIndex]->getContent(), 'not', 6) === false ? 'positive' : 'negative'];

        /** @var int $openParenthesisIndex */
        $openParenthesisIndex = $tokens->getNextMeaningfulToken($functionCallIndex);
        $closeParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesisIndex);

        if ($closeParenthesisIndex < $secondArgument->getEndIndex()) {
            return;
        }

        $tokens[$assertionIndex] = new Token([\T_STRING, $newAssertion]);
        $tokens->clearRange($secondArgument->getStartIndex(), $openParenthesisIndex - 1);
        $tokens->clearTokenAndMergeSurroundingWhitespace($openParenthesisIndex);
        $tokens->clearTokenAndMergeSurroundingWhitespace($closeParenthesisIndex);
    }
}
