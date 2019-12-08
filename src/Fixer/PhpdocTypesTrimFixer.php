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

                $variableStartPosition = \strpos($content, '$');

                if ($variableStartPosition === false) {
                    $variableStartPosition = \strlen($content);
                }

                /** @var string $types */
                $types = Preg::replace('/\h*(\||&)\h*/', '$1', \substr($content, 0, $variableStartPosition));

                $newContent = $types . \substr($content, $variableStartPosition);

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
}
