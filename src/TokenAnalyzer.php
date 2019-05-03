<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers;

use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class TokenAnalyzer
{
    /** @var Tokens */
    private $tokens;

    /**
     * @param Tokens $tokens
     */
    public function __construct(Tokens $tokens)
    {
        $this->tokens = $tokens;
    }

    public function getClosingParenthesis(int $index): ?int
    {
        if ($this->tokens[$index]->equals('(') === false) {
            throw new \Exception(\sprintf('Expected token: (. Token %d id contains %s.', $index, $this->tokens[$index]->getContent()));
        }

        for ($i = $index + 1; $i < $this->tokens->count(); $i++) {
            if ($this->tokens[$i]->equals('(')) {
                $i = $this->getClosingParenthesis($i);

                if ($i === null) {
                    return null;
                }

                continue;
            }

            if ($this->tokens[$i]->equals(')')) {
                return $i;
            }
        }

        return null;
    }

    public function getClosingBracket(int $index): ?int
    {
        if ($this->tokens[$index]->equals('[') === false) {
            throw new \Exception(\sprintf('Expected token: [. Token %d id contains %s.', $index, $this->tokens[$index]->getContent()));
        }

        for ($i = $index + 1; $i < $this->tokens->count(); $i++) {
            if ($this->tokens[$i]->equals('[')) {
                $i = $this->getClosingBracket($i);

                if ($i === null) {
                    return null;
                }

                continue;
            }

            if ($this->tokens[$i]->equals(']')) {
                return $i;
            }
        }

        return null;
    }

    public function getClosingCurlyBracket(int $index): ?int
    {
        if ($this->tokens[$index]->equals('{') === false) {
            throw new \Exception(\sprintf('Expected token: {. Token %d id contains %s.', $index, $this->tokens[$index]->getContent()));
        }

        for ($i = $index + 1; $i < $this->tokens->count(); $i++) {
            if ($this->tokens[$i]->equals('{')) {
                $i = $this->getClosingCurlyBracket($i);

                if ($i === null) {
                    return null;
                }

                continue;
            }

            if ($this->tokens[$i]->equals('}')) {
                return $i;
            }
        }

        return null;
    }

    public function getNextVariable(int $index, string $variableName, bool $ignoreBrackets): ?int
    {
        do {
            $index = $this->tokens->getNextMeaningfulToken((int) $index);

            if ($index === null) {
                return null;
            }

            if ($ignoreBrackets === true) {
                if ($this->tokens[$index]->equals('(') === true) {
                    $index = $this->getClosingParenthesis($index);
                } elseif ($this->tokens[$index]->equals('[') === true) {
                    $index = $this->getClosingBracket($index);
                } elseif ($this->tokens[$index]->equals('{') === true) {
                    $index = $this->getClosingCurlyBracket($index);
                }
            }
        } while ($this->tokens[$index]->getContent() !== $variableName);

        return $index;
    }

    public function getPreviousVariable(int $index, string $variableName, bool $ignoreBrackets): ?int
    {
        do {
            $index = $this->tokens->getPrevMeaningfulToken((int) $index);

            if ($index === null) {
                return null;
            }

            if ($ignoreBrackets === true) {
                if ($this->tokens[$index]->equals('(') === true) {
                    $index = $this->getClosingParenthesis($index);
                } elseif ($this->tokens[$index]->equals('[') === true) {
                    $index = $this->getClosingBracket($index);
                } elseif ($this->tokens[$index]->equals('{') === true) {
                    $index = $this->getClosingCurlyBracket($index);
                }
            }
        } while ($this->tokens[$index]->getContent() !== $variableName);

        return $index;
    }

    public function getNextString(string $string, int $index, bool $ignoreBrackets): ?int
    {
        do {
            $index = $this->tokens->getNextMeaningfulToken((int) $index);

            if ($index === null) {
                return null;
            }

            if ($ignoreBrackets === true) {
                if ($this->tokens[$index]->equals('(') === true) {
                    $index = $this->getClosingParenthesis($index);
                } elseif ($this->tokens[$index]->equals('[') === true) {
                    $index = $this->getClosingBracket($index);
                } elseif ($this->tokens[$index]->equals('{') === true) {
                    $index = $this->getClosingCurlyBracket($index);
                }
            }
        } while ($this->tokens[$index]->equals($string) === false);

        return $index;
    }

    public function detectIndent(int $index): string
    {
        while (true) {
            $whitespaceIndex = $this->tokens->getPrevTokenOfKind($index, [[T_WHITESPACE]]);
            if ($whitespaceIndex === null) {
                return '';
            }

            $whitespaceToken = $this->tokens[$whitespaceIndex];
            if (\strpos($whitespaceToken->getContent(), "\n") !== false) {
                break;
            }

            $prevToken = $this->tokens[$whitespaceIndex - 1];
            if ($prevToken->isGivenKind([T_OPEN_TAG, T_COMMENT]) && \substr($prevToken->getContent(), -1) === "\n") {
                break;
            }

            $index = $whitespaceIndex;
        }

        $explodedContent = \explode("\n", $whitespaceToken->getContent());

        return (string) \end($explodedContent);
    }
}
