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

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\FCT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @no-named-arguments
 */
final class NoUselessWriteVisibilityFixer extends AbstractFixer
{
    private const PREDECESSOR_KIND_MAP = [
        FCT::T_PUBLIC_SET => [\T_PUBLIC, CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC],
        FCT::T_PROTECTED_SET => [\T_PROTECTED, CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PROTECTED],
        FCT::T_PRIVATE_SET => [\T_PRIVATE, CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PRIVATE],
    ];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no useless write visibility.',
            [new CodeSample(
                <<<'PHP'
                    <?php class Foo {
                        public public(set) $x;
                        public(set) $y;
                        protected protected(set) $z;
                    }

                    PHP,
            )],
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([FCT::T_PUBLIC_SET, FCT::T_PROTECTED_SET, FCT::T_PRIVATE_SET]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens->findGivenKind([FCT::T_PUBLIC_SET, FCT::T_PROTECTED_SET, FCT::T_PRIVATE_SET]) as $kind => $elements) {
            foreach (\array_keys($elements) as $index) {
                self::fixVisibility($tokens, $index, $kind, $kind === \T_PUBLIC_SET);
            }
        }
    }

    private static function fixVisibility(Tokens $tokens, int $index, int $kind, bool $makePublicIfNone): void
    {
        $prevIndex = $tokens->getPrevMeaningfulToken($index);
        \assert(\is_int($prevIndex));
        if ($tokens[$prevIndex]->isGivenKind(\T_ABSTRACT)) {
            $prevIndex = $tokens->getPrevMeaningfulToken($prevIndex);
            \assert(\is_int($prevIndex));
        }

        if (!$tokens[$prevIndex]->isGivenKind(self::PREDECESSOR_KIND_MAP[$kind])) {
            if ($makePublicIfNone) {
                $prevDeciderIndex = $tokens->getPrevTokenOfKind($index, ['(', ';', '{']);
                \assert(\is_int($prevDeciderIndex));
                $kind = $tokens[$prevDeciderIndex]->equals('(') ? CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC : \T_PUBLIC;
                $tokens[$index] = new Token([$kind, 'public']);
            }

            return;
        }

        $tokens->clearAt($index);

        if ($tokens[$index + 1]->isWhitespace()) {
            $tokens->clearAt($index + 1);
        }
    }
}
