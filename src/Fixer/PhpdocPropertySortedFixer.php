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
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @no-named-arguments
 */
final class PhpdocPropertySortedFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Sorts @property annotations in PHPDoc blocks alphabetically within groups separated by empty lines.',
            [new CodeSample('<?php
/**
 * @property string $zzz
 * @property int $aaa
 * @property bool $mmm
 */
class Foo {}
')],
            '',
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_DOC_COMMENT);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(\T_DOC_COMMENT)) {
                continue;
            }

            $originalDocContent = $token->getContent();
            $sortedDocContent = self::sortPropertiesInDocBlock($originalDocContent);

            if ($originalDocContent !== $sortedDocContent) {
                $tokens[$index] = new Token([\T_DOC_COMMENT, $sortedDocContent]);
            }
        }
    }

    private static function sortPropertiesInDocBlock(string $docContent): string
    {
        $docLines = \explode("\n", $docContent);
        $processedLines = [];
        $currentPropertyGroup = [];

        foreach ($docLines as $line) {
            if (self::isPropertyAnnotation($line)) {
                $currentPropertyGroup[] = $line;
            } else {
                self::flushPropertyGroup($currentPropertyGroup, $processedLines);
                $processedLines[] = $line;
            }
        }

        return \implode("\n", $processedLines);
    }

    private static function isPropertyAnnotation(string $line): bool
    {
        return Preg::match('/@property/', $line);
    }

    /**
     * Sorts and adds a property group to the processed lines.
     *
     * @param list<string> $propertyGroup
     * @param list<string> $processedLines
     */
    private static function flushPropertyGroup(array &$propertyGroup, array &$processedLines): void
    {
        if (\count($propertyGroup) === 0) {
            return;
        }

        self::sortPropertiesByName($propertyGroup);
        $processedLines = \array_merge($processedLines, $propertyGroup);
        $propertyGroup = [];
    }

    /**
     * @param list<string> $properties
     */
    private static function sortPropertiesByName(array &$properties): void
    {
        \usort($properties, static function (string $propertyA, string $propertyB): int {
            $nameA = self::extractPropertyName($propertyA);
            $nameB = self::extractPropertyName($propertyB);

            return \strcmp($nameA ?? '', $nameB ?? '');
        });
    }

    private static function extractPropertyName(string $propertyLine): ?string
    {
        $matches = [];
        Preg::match('/@property(?:-read|-write)?\\s+[^\\s]+\\s+\\$(\\w+)/', $propertyLine, $matches);
        /** @var array<array-key, string> $matches */
        if (\count($matches) > 1) {
            return $matches[1];
        }

        return null;
    }
}
