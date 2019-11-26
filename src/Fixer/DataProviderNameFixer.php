<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\DataProviderAnalyzer;

final class DataProviderNameFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Name of data provider that is used only once must match name of test.',
            [
                new CodeSample(
                    '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider dataProvider
     */
    public function testHappyPath() {}
    public function dataProvider() {}
}
'
                ),
            ],
            null,
            'when relying on name of data provider function'
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
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();

        /** @var int[] $indices */
        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indices) {
            $this->fixNames($tokens, $indices[0], $indices[1]);
        }
    }

    private function fixNames(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        $dataProviderAnalyzer = new DataProviderAnalyzer();
        foreach ($dataProviderAnalyzer->getDataProviders($tokens, $startIndex, $endIndex) as $dataProviderAnalysis) {
            if (\count($dataProviderAnalysis->getUsageIndices()) > 1) {
                continue;
            }
            $usageIndex = $dataProviderAnalysis->getUsageIndices()[0];
            $testNameIndex = $tokens->getNextTokenOfKind($usageIndex, [[T_STRING]]);

            $dataProviderNewName = $this->getProviderNameForTestName($tokens[$testNameIndex]->getContent());
            if ($tokens->findSequence([[T_FUNCTION], [T_STRING, $dataProviderNewName]], $startIndex, $endIndex, false) !== null) {
                continue;
            }

            $tokens[$dataProviderAnalysis->getNameIndex()] = new Token([T_STRING, $dataProviderNewName]);

            /** @var string $newCommentContent */
            $newCommentContent = Preg::replace(
                \sprintf('/(@dataProvider\s+)%s/', $dataProviderAnalysis->getName()),
                \sprintf('$1%s', $dataProviderNewName),
                $tokens[$usageIndex]->getContent()
            );

            $tokens[$usageIndex] = new Token([T_DOC_COMMENT, $newCommentContent]);
        }
    }

    private function getProviderNameForTestName(string $name): string
    {
        if (Preg::match('/^test/', $name) === 1) {
            $name = \substr($name, 4);
        }

        return 'provide' . \ucfirst($name) . 'Cases';
    }
}
