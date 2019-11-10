<?php

declare(strict_types = 1);

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
        // Must be run after MultilineCommentOpeningClosingFixer and NoTrailingWhitespaceInCommentFixer
        // Must be run after PhpdocTrimFixer
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
        foreach ($tokens as $index => $token) {
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

        if ($matches['before_newline'][0] !== ' ') {
            $indent .= ' ';
        }

        $newContent = $matches['opening'] . $matches['newline'] . $indent . $matches['before_newline'] . $matches['newline'] . $matches['after_newline'];

        if ($newContent !== $token->getContent()) {
            $tokens[$index] = new Token([Preg::match('~/\*{2}\s~', $newContent) === 1 ? T_DOC_COMMENT : T_COMMENT, $newContent]);
        }
    }

    private function fixClosing(Tokens $tokens, int $index): void
    {
        $token = $tokens[$index];

        if (Preg::match('#\R\h*\*+/$#', $token->getContent()) === 1) {
            return;
        }

        Preg::match('#\R(\h*)#', $token->getContent(), $matches);
        $indent = $matches[1];

        $newContent = Preg::replace('#(\R)(.+?)\h*(\*+/)$#', \sprintf('$1$2$1%s$3', $indent), $token->getContent());

        if ($newContent !== $token->getContent()) {
            $tokens[$index] = new Token([$token->getId(), $newContent]);
        }
    }
}
