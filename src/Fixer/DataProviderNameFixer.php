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
            'Name of data provider that is used only once must match name of test.',
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

    public function getPriority(): int
    {
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

        /** @var int[] $indices */
        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indices) {
            $this->fixNames($tokens, $indices[0], $indices[1]);
        }
    }

    private function fixNames(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        /** @var array<string, array<string, int>> $functions */
        $functions = $this->getFunctions($tokens, $startIndex, $endIndex);

        /** @var array<string, string> $dataProvidersToRename */
        $dataProvidersToRename = $this->getDataProvidersToRename($functions);

        foreach ($dataProvidersToRename as $dataProviderName => $testName) {
            if (!\array_key_exists($dataProviderName, $functions)) {
                continue;
            }

            $dataProviderNewName = $this->getProviderNameForTestName($testName);
            if (\array_key_exists($dataProviderNewName, $functions)) {
                continue;
            }

            $tokens[$functions[$dataProviderName]['name_index']] = new Token([T_STRING, $dataProviderNewName]);
            $functions[$dataProviderNewName] = [];

            /** @var string $newCommentContent */
            $newCommentContent = Preg::replace(
                \sprintf('/(@dataProvider\s+)%s/', $dataProviderName),
                \sprintf('$1%s', $dataProviderNewName),
                $tokens[$functions[$testName]['doc_comment_index']]->getContent()
            );

            $tokens[$functions[$testName]['doc_comment_index']] = new Token([T_DOC_COMMENT, $newCommentContent]);
        }
    }

    private function getFunctions(Tokens $tokens, int $startIndex, int $endIndex): array
    {
        $functions = [];
        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (!$tokens[$index]->isGivenKind(T_FUNCTION)) {
                continue;
            }

            /** @var int $functionNameIndex */
            $functionNameIndex = $tokens->getNextNonWhitespace($index);
            if (!$tokens[$functionNameIndex]->isGivenKind(T_STRING)) {
                continue;
            }
            $indices = ['name_index' => $functionNameIndex];

            /** @var int $docCommentIndex */
            $docCommentIndex = $tokens->getTokenNotOfKindSibling(
                $index,
                -1,
                [[T_ABSTRACT], [T_COMMENT], [T_FINAL], [T_PRIVATE], [T_PROTECTED], [T_PUBLIC], [T_STATIC], [T_WHITESPACE]]
            );
            if ($tokens[$docCommentIndex]->isGivenKind(T_DOC_COMMENT)) {
                Preg::matchAll('/@dataProvider\s+([a-zA-Z0-9._:-\\\\x7f-\xff]+)/', $tokens[$docCommentIndex]->getContent(), $matches);

                $indices['doc_comment_index'] = $docCommentIndex;
                $indices['data_provider_names'] = $matches[1];
            }

            $functions[$tokens[$functionNameIndex]->getContent()] = $indices;
        }

        return $functions;
    }

    /**
     * @param array<string, array> $functions
     */
    private function getDataProvidersToRename(array $functions): array
    {
        $dataProvidersUses = [];
        foreach ($functions as $name => $indices) {
            if (!\array_key_exists('data_provider_names', $indices)) {
                continue;
            }
            /** @var string $provider */
            foreach ($indices['data_provider_names'] as $provider) {
                if (\array_key_exists($provider, $dataProvidersUses)) {
                    $dataProvidersUses[$provider] = '';
                    continue;
                }
                $dataProvidersUses[$provider] = $name;
            }
        }

        return \array_filter(
            $dataProvidersUses,
            static function (string $name): bool {
                return $name !== '';
            }
        );
    }

    private function getProviderNameForTestName(string $name): string
    {
        if (Preg::match('/^test/', $name) === 1) {
            $name = \substr($name, 4);
        }

        return 'provide' . \ucfirst($name) . 'Cases';
    }
}
