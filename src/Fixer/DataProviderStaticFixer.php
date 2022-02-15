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

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use PhpCsFixerCustomFixers\Analyzer\DataProviderAnalyzer;

final class DataProviderStaticFixer extends AbstractFixer implements ConfigurableFixerInterface
{
    /** @var bool */
    private $force = false;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Data providers must be static.',
            [
                new CodeSample(
                    '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideSomethingCases
     */
    public function testSomething($expected, $actual) {}
    public function provideSomethingCases() {}
}
'
                ),
            ],
            null,
            'when `force` is set to `true`'
        );
    }

    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('force', 'whether to make static data providers having dynamic class calls'))
                ->setAllowedTypes(['bool'])
                ->setDefault($this->force)
                ->getOption(),
        ]);
    }

    /**
     * @param array<string, bool> $configuration
     */
    public function configure(array $configuration): void
    {
        if (\array_key_exists('force', $configuration)) {
            $this->force = $configuration['force'];
        }
    }

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
        return $this->force;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();

        /** @var array<int> $indices */
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

                if (!$this->force && $tokens->findSequence([[\T_VARIABLE, '$this']], $methodStartIndex, $methodEndIndex) !== null) {
                    continue;
                }
            }
            $functionIndex = $tokens->getPrevTokenOfKind($dataProviderAnalysis->getNameIndex(), [[\T_FUNCTION]]);
            \assert(\is_int($functionIndex));

            $methodAttributes = $tokensAnalyzer->getMethodAttributes($functionIndex);
            if ($methodAttributes['static'] !== false) {
                continue;
            }

            $tokens->insertAt(
                $functionIndex,
                [new Token([\T_STATIC, 'static']), new Token([\T_WHITESPACE, ' '])]
            );
        }
    }
}
