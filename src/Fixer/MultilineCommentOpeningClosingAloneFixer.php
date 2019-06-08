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
            'multiline comment/PHPDoc must have opening and closing line without any extra content',
            [new CodeSample("<?php\n/** Hello\n * World!\n */;\n")]
        );
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

            $content = $token->getContent();

            if (Preg::match('/\R/', $content) !== 1) {
                continue;
            }

            $toFixOpening = Preg::match('#^/\*+\R#', $content) !== 1;
            $toFixClosing = Preg::match('#\R\h*\*+/$#', $content) !== 1;

            if (!$toFixOpening && !$toFixClosing) {
                continue;
            }

            Preg::match('#\R(\h*)#', $content, $matches);
            $indent = $matches[1];

            if ($toFixOpening) {
                Preg::match('#^(/\*+)(.*?)(\R)(.*)$#s', $content, $matches);
                if ($matches === []) {
                    continue;
                }
                if ($matches[2][0] === '/') {
                    $matches[2] = ' ' . $matches[2];
                }
                $content = $matches[1] . $matches[3] . $indent . '*' . $matches[2] . $matches[3] . $matches[4];
            }

            if ($toFixClosing) {
                $content = Preg::replace('#(\R)([^\R]+?)\h*(\*+/)$#', \sprintf('$1$2$1%s$3', $indent), $content);
            }

            $tokens[$index] = new Token([$token->getId(), $content]);
        }
    }

    public function getPriority(): int
    {
        // Must be run after MultilineCommentOpeningClosingFixer and NoTrailingWhitespaceInCommentFixer
        // Must be run before PhpdocTrimFixer
        return -1;
    }
}
