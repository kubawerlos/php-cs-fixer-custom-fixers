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

use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\ConstructorAnalyzer;

final class ReadonlyPromotedPropertiesFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Promoted properties must readonly.',
            [
                new VersionSpecificCodeSample(
                    '<?php class Foo {
    public function __construct(
        public array $a,
        public bool $b,
    ) {}
}
',
                    new VersionSpecification(80100),
                ),
            ],
            '',
            'when property is written',
        );
    }

    /**
     * Must run after PromotedConstructorPropertyFixer.
     */
    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return \defined('T_READONLY') && $tokens->isAnyTokenKindsFound([
            CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PRIVATE,
            CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PROTECTED,
            CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC,
        ]);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $constructorAnalyzer = new ConstructorAnalyzer();

        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind(\T_CLASS)) {
                continue;
            }

            $constructorAnalysis = $constructorAnalyzer->findNonAbstractConstructor($tokens, $index);
            if ($constructorAnalysis === null) {
                continue;
            }

            $openParenthesis = $tokens->getNextTokenOfKind($constructorAnalysis->getConstructorIndex(), ['(']);
            \assert(\is_int($openParenthesis));
            $closeParenthesis = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesis);

            $this->fixParameters($tokens, $openParenthesis, $closeParenthesis);
        }
    }

    private function fixParameters(Tokens $tokens, int $openParenthesis, int $closeParenthesis): void
    {
        for ($index = $closeParenthesis; $index > $openParenthesis; $index--) {
            if (
                !$tokens[$index]->isGivenKind([
                    CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PRIVATE,
                    CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PROTECTED,
                    CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC,
                ])
            ) {
                continue;
            }

            $nextIndex = $tokens->getNextMeaningfulToken($index);
            if ($tokens[$nextIndex]->isGivenKind(\T_READONLY)) {
                continue;
            }

            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            if ($tokens[$prevIndex]->isGivenKind(\T_READONLY)) {
                continue;
            }

            $tokens->insertAt(
                $index + 1,
                [
                    new Token([\T_WHITESPACE, ' ']),
                    new Token([\T_READONLY, 'readonly']),
                ],
            );
        }
    }
}
