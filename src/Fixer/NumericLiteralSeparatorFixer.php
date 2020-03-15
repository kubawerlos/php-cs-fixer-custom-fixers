<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NumericLiteralSeparatorFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /** @var null|bool */
    private $binarySeparator = false;

    /** @var null|bool */
    private $decimalSeparator = false;

    /** @var null|bool */
    private $floatSeparator = false;

    /** @var null|bool */
    private $hexadecimalSeparator = false;

    /** @var null|bool */
    private $octalSeparator = false;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Numeric literals must have configured separators.',
            [new VersionSpecificCodeSample(
                '<?php
echo 0b01010100_01101000; // binary
echo 299_792_458; // decimal
echo 1_200.674_083e-11; // float
echo 0xCAFE_F00D; // hexadecimal
echo 0137_041; // octal
',
                new VersionSpecification(70400)
            )]
        );
    }

    public function getConfigurationDefinition(): FixerConfigurationResolver
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('binary', 'whether add, remove or ignore separators in binary numbers.'))
                ->setAllowedTypes(['bool', 'null'])
                ->setDefault($this->binarySeparator)
                ->getOption(),
            (new FixerOptionBuilder('decimal', 'whether add, remove or ignore separators in decimal numbers.'))
                ->setAllowedTypes(['bool', 'null'])
                ->setDefault($this->decimalSeparator)
                ->getOption(),
            (new FixerOptionBuilder('float', 'whether add, remove or ignore separators in float numbers.'))
                ->setAllowedTypes(['bool', 'null'])
                ->setDefault($this->floatSeparator)
                ->getOption(),
            (new FixerOptionBuilder('hexadecimal', 'whether add, remove or ignore separators in hexadecimal numbers.'))
                ->setAllowedTypes(['bool', 'null'])
                ->setDefault($this->hexadecimalSeparator)
                ->getOption(),
            (new FixerOptionBuilder('octal', 'whether add, remove or ignore separators in octal numbers.'))
                ->setAllowedTypes(['bool', 'null'])
                ->setDefault($this->octalSeparator)
                ->getOption(),
        ]);
    }

    public function configure(?array $configuration = null): void
    {
        /** @var array<null|bool> $configuration */
        $configuration = $configuration ?? [];

        $this->binarySeparator = \array_key_exists('binary', $configuration) ? $configuration['binary'] : $this->binarySeparator;
        $this->decimalSeparator = \array_key_exists('decimal', $configuration) ? $configuration['decimal'] : $this->decimalSeparator;
        $this->floatSeparator = \array_key_exists('float', $configuration) ? $configuration['float'] : $this->floatSeparator;
        $this->hexadecimalSeparator = \array_key_exists('hexadecimal', $configuration) ? $configuration['hexadecimal'] : $this->hexadecimalSeparator;
        $this->octalSeparator = \array_key_exists('octal', $configuration) ? $configuration['octal'] : $this->octalSeparator;
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return \PHP_VERSION_ID >= 70400 && $tokens->isAnyTokenKindsFound([T_DNUMBER, T_LNUMBER]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind([T_DNUMBER, T_LNUMBER])) {
                continue;
            }

            $content = $tokens[$index]->getContent();
            $newContent = $this->getNewContent($content);

            if ($content !== $newContent) {
                $tokens[$index] = new Token([$tokens[$index]->getId(), $newContent]);
            }
        }
    }

    private function getNewContent(string $content): string
    {
        if (\strpos($content, '.') !== false) {
            $content = $this->updateContent($content, null, '.', 3, $this->floatSeparator);
            $content = $this->updateContent($content, '.', 'e', 3, $this->floatSeparator, false);

            return $this->updateContent($content, 'e', null, 3, $this->floatSeparator);
        }

        if (\stripos($content, '0b') === 0) {
            return $this->updateContent($content, 'b', null, 8, $this->binarySeparator);
        }

        if (\stripos($content, '0x') === 0) {
            return $this->updateContent($content, 'x', null, 2, $this->hexadecimalSeparator);
        }

        if (\strpos($content, '0') === 0) {
            return $this->updateContent($content, '0', null, 4, $this->octalSeparator);
        }

        if (Preg::match('/e-?\d+$/i', $content) === 1) {
            $content = $this->updateContent($content, null, 'e', 3, $this->floatSeparator);

            return $this->updateContent($content, 'e', null, 3, $this->floatSeparator);
        }

        return $this->updateContent($content, null, null, 3, $this->decimalSeparator);
    }

    private function updateContent(string $content, ?string $startCharacter, ?string $endCharacter, int $groupSize, ?bool $addSeparators, bool $fromRight = true): string
    {
        if ($addSeparators === null) {
            return $content;
        }

        $startPosition = $this->getStartPosition($content, $startCharacter);
        if ($startPosition === null) {
            return $content;
        }
        $endPosition = $this->getEndPosition($content, $endCharacter);

        $substringToUpdate = \substr($content, $startPosition, $endPosition - $startPosition);
        $substringToUpdate = \str_replace('_', '', $substringToUpdate);

        if ($addSeparators) {
            if ($fromRight) {
                $substringToUpdate = \strrev($substringToUpdate);
            }

            /** @var string $substringToUpdate */
            $substringToUpdate = Preg::replace(\sprintf('/[\da-fA-F]{%d}(?!$)/', $groupSize), '$0_', $substringToUpdate);

            if ($fromRight) {
                $substringToUpdate = \strrev($substringToUpdate);
            }
        }

        return \substr($content, 0, $startPosition) . $substringToUpdate . \substr($content, $endPosition);
    }

    private function getStartPosition(string $content, ?string $startCharacter): ?int
    {
        if ($startCharacter === null) {
            return 0;
        }
        $startPosition = \stripos($content, $startCharacter);
        if ($startPosition === false) {
            return null;
        }
        if ($startPosition !== 0) {
            $startPosition++;
        }

        return $startPosition;
    }

    private function getEndPosition(string $content, ?string $endCharacter): int
    {
        $endPosition = $endCharacter === null ? \strlen($content) : \stripos($content, $endCharacter);

        return $endPosition === false ? \strlen($content) : $endPosition;
    }
}
