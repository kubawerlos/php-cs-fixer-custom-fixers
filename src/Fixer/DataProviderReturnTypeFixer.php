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
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\DataProviderAnalyzer;

final class DataProviderReturnTypeFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'The types returned by data providers must be `iterable`.',
            [
                new CodeSample(
                    '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideSomethingCases
     */
    public function testSomething($expected, $actual) {}
    public function provideSomethingCases(): array {}
}
'
                ),
            ],
            null,
            'when relying on signature of data provider'
        );
    }

    /**
     * Must run before ReturnTypeDeclarationFixer.
     */
    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([\T_CLASS, \T_DOC_COMMENT, \T_EXTENDS, \T_FUNCTION, \T_STRING]);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();

        /** @var array<int> $indices */
        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indices) {
            $this->fixReturnTypes($tokens, $indices[0], $indices[1]);
        }
    }

    private function fixReturnTypes(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        $dataProviderAnalyzer = new DataProviderAnalyzer();
        $functionsAnalyzer = new FunctionsAnalyzer();

        foreach (\array_reverse($dataProviderAnalyzer->getDataProviders($tokens, $startIndex, $endIndex)) as $dataProviderAnalysis) {
            $typeAnalysis = $functionsAnalyzer->getFunctionReturnType($tokens, $dataProviderAnalysis->getNameIndex());

            if ($typeAnalysis === null) {
                /** @var int $argumentsStart */
                $argumentsStart = $tokens->getNextTokenOfKind($dataProviderAnalysis->getNameIndex(), ['(']);
                $argumentsEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $argumentsStart);
                $tokens->insertAt(
                    $argumentsEnd + 1,
                    [
                        new Token([CT::T_TYPE_COLON, ':']),
                        new Token([\T_WHITESPACE, ' ']),
                        new Token([\T_STRING, 'iterable']),
                    ]
                );
                continue;
            }

            if ($typeAnalysis->getName() !== 'iterable') {
                /** @var int $startIndex */
                $startIndex = $tokens->getNextMeaningfulToken($typeAnalysis->getStartIndex() - 1);
                $tokens->overrideRange($startIndex, $typeAnalysis->getEndIndex(), [new Token([\T_STRING, 'iterable'])]);
            }
        }
    }
}
