<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\TypeAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class DataProviderReturnTypeFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Return type of data provider must be `iterable`.',
            [
                new CodeSample(
                    '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideHappyPathCases
     */
    function testHappyPath() {}
    function provideHappyPathCases(): array {}
}
'
                ),
            ],
            null,
            'when relying on signature of data provider'
        );
    }

    public function getPriority(): int
    {
        // must be run before MethodArgumentSpaceFixer
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();
        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indexes) {
            $this->fixNames($tokens, $indexes[0], $indexes[1]);
        }
    }

    private function fixNames(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        $functionsAnalyzer = new FunctionsAnalyzer();

        $dataProviderNames = $this->getDataProviderNames($tokens, $startIndex, $endIndex);

        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (!$tokens[$index]->isGivenKind(T_FUNCTION)) {
                continue;
            }

            /** @var int $functionNameIndex */
            $functionNameIndex = $tokens->getNextNonWhitespace($index);

            if (!$tokens[$functionNameIndex]->isGivenKind(T_STRING)) {
                continue;
            }

            if (!isset($dataProviderNames[$tokens[$functionNameIndex]->getContent()])) {
                continue;
            }

            $typeAnalysis = $functionsAnalyzer->getFunctionReturnType($tokens, $functionNameIndex);

            if ($typeAnalysis === null) {
                /** @var int $argumentsStart */
                $argumentsStart = $tokens->getNextTokenOfKind($functionNameIndex, ['(']);
                $argumentsEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $argumentsStart);
                $tokens->insertAt(
                    $argumentsEnd + 1,
                    [
                        new Token([CT::T_TYPE_COLON, ':']),
                        new Token([T_WHITESPACE, ' ']),
                        new Token([T_STRING, 'iterable']),
                    ]
                );
                continue;
            }

            if ($this->getTypeName($tokens, $typeAnalysis) !== 'iterable') {
                /** @var int $startIndex */
                $startIndex = $tokens->getNextMeaningfulToken($typeAnalysis->getStartIndex() - 1);
                $tokens->overrideRange($startIndex, $typeAnalysis->getEndIndex(), [new Token([T_STRING, 'iterable'])]);
            }
        }
    }

    /**
     * @return string[]
     */
    private function getDataProviderNames(Tokens $tokens, int $startIndex, int $endIndex): array
    {
        $dataProviderNames = [];

        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (!$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            /** @var int $functionIndex */
            $functionIndex = $tokens->getTokenNotOfKindSibling(
                $index,
                1,
                [[T_ABSTRACT], [T_COMMENT], [T_FINAL], [T_PRIVATE], [T_PROTECTED], [T_PUBLIC], [T_STATIC], [T_WHITESPACE]]
            );

            if (!$tokens[$functionIndex]->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $functionNameIndex = $tokens->getNextNonWhitespace($functionIndex);
            if (!$tokens[$functionNameIndex]->isGivenKind(T_STRING)) {
                continue;
            }

            Preg::matchAll('/@dataProvider\s+([a-zA-Z0-9._:-\\\\x7f-\xff]+)/', $tokens[$index]->getContent(), $matches);

            /** @var string[] $matches */
            $matches = $matches[1];

            foreach ($matches as $match) {
                $dataProviderNames[$match] = $match;
            }
        }

        return $dataProviderNames;
    }

    /**
     * TODO: remove this function after https://github.com/FriendsOfPHP/PHP-CS-Fixer/pull/4581 is released.
     */
    private function getTypeName(Tokens $tokens, TypeAnalysis $typeAnalysis): string
    {
        $type = '';
        for ($index = $typeAnalysis->getStartIndex(); $index <= $typeAnalysis->getEndIndex(); $index++) {
            if ($tokens[$index]->isWhitespace() || $tokens[$index]->isComment()) {
                continue;
            }

            $type .= $tokens[$index]->getContent();
        }

        return $type;
    }
}
