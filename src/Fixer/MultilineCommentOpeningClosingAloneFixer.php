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
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class MultilineCommentOpeningClosingAloneFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Multiline comment/PHPDoc must have opening and closing line without any extra content.',
            [new CodeSample("<?php\n/** Hello\n * World!\n */;\n")]
        );
    }

    public function getPriority(): int
    {
        // must be run before MultilineCommentOpeningClosingFixer
        return 1;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_COMMENT, T_DOC_COMMENT]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->isGivenKind([T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }

            if (Preg::match('/\R/', $token->getContent()) !== 1) {
                continue;
            }

            $this->fixOpening($tokens, $index);
            $this->fixClosing($tokens, $index);
        }
    }

    private function fixOpening(Tokens $tokens, int $index): void
    {
        /** @var Token $token */
        $token = $tokens[$index];

        if (Preg::match('#^/\*+\R#', $token->getContent()) === 1) {
            return;
        }

        Preg::match('#\R(\h*)#', $token->getContent(), $matches);
        $indent = $matches[1] . '*';

        Preg::match('#^(?<opening>/\*+)(?<before_newline>.*?)(?<newline>\R)(?<after_newline>.*)$#s', $token->getContent(), $matches);
        if ($matches === []) {
            return;
        }

        /** @var string $opening */
        $opening = $matches['opening'];

        /** @var string $beforeNewline */
        $beforeNewline = $matches['before_newline'];

        /** @var string $newline */
        $newline = $matches['newline'];

        /** @var string $afterNewline */
        $afterNewline = $matches['after_newline'];

        if ($beforeNewline[0] !== ' ') {
            $indent .= ' ';
        }

        if (Preg::match('#^\h+$#', $beforeNewline) === 1) {
            $insert = '';
        } else {
            $insert = $newline . $indent . $beforeNewline;
        }

        $newContent = $opening . $insert . $newline . $afterNewline;

        if ($newContent !== $token->getContent()) {
            $tokens[$index] = new Token([Preg::match('~/\*{2}\s~', $newContent) === 1 ? T_DOC_COMMENT : T_COMMENT, $newContent]);
        }
    }

    private function fixClosing(Tokens $tokens, int $index): void
    {
        /** @var Token $token */
        $token = $tokens[$index];

        if (Preg::match('#\R\h*\*+/$#', $token->getContent()) === 1) {
            return;
        }

        Preg::match('#\R(\h*)#', $token->getContent(), $matches);

        /** @var string $indent */
        $indent = $matches[1];

        $newContent = Preg::replace('#(\R)(.+?)\h*(\*+/)$#', \sprintf('$1$2$1%s$3', $indent), $token->getContent());

        if ($newContent !== $token->getContent()) {
            $tokens[$index] = new Token([$token->getId(), $newContent]);
        }
    }
}
