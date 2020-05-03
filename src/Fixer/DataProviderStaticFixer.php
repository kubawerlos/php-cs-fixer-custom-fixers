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
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use PhpCsFixerCustomFixers\Analyzer\DataProviderAnalyzer;

final class DataProviderStaticFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Data provider must be static.',
            [
                new CodeSample(
                    '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideHappyPathCases
     */
    public function testHappyPath() {}
    public function provideHappyPathCases() {}
}
'
                ),
            ]
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([T_CLASS, T_DOC_COMMENT, T_EXTENDS, T_FUNCTION, T_STRING]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();

        /** @var int[] $indices */
        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indices) {
            $this->fixStatic($tokens, $indices[0], $indices[1]);
        }
    }

    private function fixStatic(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        $dataProviderAnalyzer = new DataProviderAnalyzer();
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        foreach (\array_reverse($dataProviderAnalyzer->getDataProviders($tokens, $startIndex, $endIndex)) as $dataProviderAnalysis) {
            $methodStartIndex = $tokens->getNextTokenOfKind($dataProviderAnalysis->getNameIndex(), ['{']);
            if ($methodStartIndex !== null) {
                $methodEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $methodStartIndex);

                if ($tokens->findSequence([[T_VARIABLE, '$this']], $methodStartIndex, $methodEndIndex) !== null) {
                    continue;
                }
            }
            /** @var int $functionIndex */
            $functionIndex = $tokens->getPrevTokenOfKind($dataProviderAnalysis->getNameIndex(), [[T_FUNCTION]]);

            $methodAttributes = $tokensAnalyzer->getMethodAttributes($functionIndex);
            if ($methodAttributes['static'] !== false) {
                continue;
            }

            $tokens->insertAt(
                $functionIndex,
                [new Token([T_STATIC, 'static']), new Token([T_WHITESPACE, ' '])]
            );
        }
    }
}
