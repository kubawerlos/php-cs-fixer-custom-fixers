<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\TokensAdapter;

final class PhpdocSingleLineVarFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            '`@var` annotation must be in single line when is the only content.',
            [new CodeSample('<?php
class Foo {
    /**
     * @var string
     */
    private $name;
}
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

    protected function applyFix(\SplFileInfo $file, TokensAdapter $tokens): void
    {
        foreach ($tokens->toArray() as $index => $token) {
            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            if (\stripos($token->getContent(), '@var') === false) {
                continue;
            }

            if (Preg::match('#^/\*\*[\s\*]+(@var[^\r\n]+)[\s\*]*\*\/$#u', $token->getContent(), $matches) !== 1) {
                continue;
            }

            $newContent = '/** ' . \rtrim($matches[1]) . ' */';

            if ($newContent === $token->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([T_DOC_COMMENT, $newContent]);
        }
    }
}
