<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocTypesTrimFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'PHPDoc types must be trimmed.',
            [new CodeSample('<?php
/**
 * @param null | string $x
 */
function foo($x) {}
')]
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
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            $token = $tokens[$index];

            if (!$token->isGivenKind([T_DOC_COMMENT])) {
                continue;
            }

            $docBlock = new DocBlock($token->getContent());

            foreach ($docBlock->getAnnotations() as $annotation) {
                if (!$annotation->supportTypes()) {
                    continue;
                }

                /** @var Line $line */
                $line = $docBlock->getLine($annotation->getStart());

                $content = $line->getContent();
                $newContent = $this->trimTypes($content);

                /** @var Line $line */
                $line = $docBlock->getLine($annotation->getStart());
                $line->setContent($newContent);
            }

            $newContent = $docBlock->getContent();
            if ($newContent === $token->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([T_DOC_COMMENT, $newContent]);
        }
    }

    private function trimTypes(string $content): string
    {
        if (\strpos($content, '|') === false && \strpos($content, '&') === false) {
            return $content;
        }

        $variableStartPosition = \strpos($content, '$');
        if ($variableStartPosition === false) {
            $variableStartPosition = \strlen($content);
        }

        /** @var int $tagStartPosition */
        $tagStartPosition = \strpos($content, '@');

        /** @var int $spaceAfterTag */
        $spaceAfterTag = \strpos($content, ' ', $tagStartPosition);

        Preg::match('/(?<!(&|\|))\h(?!(&(?!\$)|\|))/', $content, $matches, PREG_OFFSET_CAPTURE, $spaceAfterTag + 1);
        if ($matches !== []) {
            $descriptionStartPosition = $matches[0][1];
        } else {
            $descriptionStartPosition = \strlen($content);
        }
        $length = \min($variableStartPosition, $descriptionStartPosition);

        $contentToUpdate = \substr($content, 0, $length);
        $contentNotToUpdate = \substr($content, $length);

        /** @var string $trimmedContent */
        $trimmedContent = Preg::replace('/\h*(&|\|)\h*/', '$1', $contentToUpdate);

        return $trimmedContent . $contentNotToUpdate;
    }
}
