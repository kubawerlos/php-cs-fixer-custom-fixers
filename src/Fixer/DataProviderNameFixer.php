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

final class DataProviderNameFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'name of data provider that is used only once must match name of test',
            [
                new CodeSample(
                    '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider dataProvider
     */
    function testHappyPath() {}
    function dataProvider() {}
}
'
                ),
            ],
            null,
            'when relying on name of data provider function'
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([T_CLASS, T_DOC_COMMENT]);
    }

    public function getPriority(): int
    {
        return 0;
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
        $dataProviderCallIndices = [];
        $dataProviderUsagesCounts = [];
        $dataProviderUsingFunctionNames = [];
        $functionDefinitionIndices = [];
        for ($index = $startIndex; $index < $endIndex; $index++) {
            // if it's the function and string follows then it's function's definition
            if ($tokens[$index]->isGivenKind(T_FUNCTION)) {
                $functionNameIndex = $tokens->getNextNonWhitespace($index);
                if ($tokens[$functionNameIndex]->isGivenKind(T_STRING)) {
                    $functionDefinitionIndices[$tokens[$functionNameIndex]->getContent()] = $functionNameIndex;
                }
                continue;
            }

            // as it's not function's definition we search for data provider usage

            if (!$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            /** @var int $functionIndex */
            $functionIndex = $tokens->getTokenNotOfKindSibling(
                $index,
                1,
                [[T_WHITESPACE], [T_COMMENT], [T_DOC_COMMENT], [T_ABSTRACT], [T_FINAL], [T_PUBLIC], [T_PROTECTED], [T_PRIVATE], [T_STATIC]]
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
                if (!isset($dataProviderUsagesCounts[$match])) {
                    $dataProviderUsagesCounts[$match] = 0;
                }
                $dataProviderUsagesCounts[$match]++;

                $dataProviderCallIndices[$match] = $index;

                $dataProviderUsingFunctionNames[$match] = $tokens[$functionNameIndex]->getContent();
            }
        }

        foreach ($dataProviderUsagesCounts as $dataProviderName => $numberOfCalls) {
            if ($numberOfCalls > 1) {
                continue;
            }

            if (!isset($functionDefinitionIndices[$dataProviderName])) {
                continue;
            }

            $dataProviderNewName = $this->getProviderNameForTestName($dataProviderUsingFunctionNames[$dataProviderName]);
            if (isset($functionDefinitionIndices[$dataProviderNewName])) {
                continue;
            }

            $tokens[$functionDefinitionIndices[$dataProviderName]] = new Token([T_STRING, $dataProviderNewName]);

            /** @var string $newCommentContent */
            $newCommentContent = Preg::replace(
                \sprintf('/(@dataProvider\s+)%s/', $dataProviderName),
                \sprintf('$1%s', $dataProviderNewName),
                $tokens[$dataProviderCallIndices[$dataProviderName]]->getContent()
            );

            $tokens[$dataProviderCallIndices[$dataProviderName]] = new Token([T_DOC_COMMENT, $newCommentContent]);
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
