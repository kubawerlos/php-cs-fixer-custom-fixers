<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixersDev\Fixer;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Utils;
use PhpCsFixerCustomFixersDev\OrderedClassElementsFixerWrapper;

/**
 * @internal
 */
final class OrderedClassElementsInternalFixer implements FixerInterface
{
    public const PUBLIC_METHODS_ORDER = [
        'getDefinition',
        'getConfigurationDefinition',
        'configure',
        'setWhitespacesConfig',
        'name',
        'getName',
        'getPriority',
        'getPullRequestId',
        'supports',
        'isCandidate',
        'isRisky',
        'fix',
        'getSuccessorsNames',
    ];

    /** @var OrderedClassElementsFixerWrapper */
    private $orderedClassElementsFixerWrapper;

    public function __construct()
    {
        $this->orderedClassElementsFixerWrapper = new OrderedClassElementsFixerWrapper();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition('Internal fixer for class elements order.', []);
    }

    public function getName(): string
    {
        return 'Internal/' . \strtolower(\str_replace('\\', '_', Utils::camelCaseToUnderscore(__CLASS__)));
    }

    public function getPriority(): int
    {
        return $this->orderedClassElementsFixerWrapper->getPriority();
    }

    public function supports(\SplFileInfo $file): bool
    {
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->findSequence([[\T_EXTENDS], [\T_STRING, 'AbstractFixer']]) !== null
            || $tokens->findSequence([[\T_IMPLEMENTS], [\T_STRING, 'FixerInterface']]) !== null;
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 1; $index < $tokens->count(); $index++) {
            if (!$tokens[$index]->isClassy()) {
                continue;
            }

            $index = $tokens->getNextTokenOfKind($index, ['{']);
            \assert(\is_int($index));

            /** @var array<array<string>> $elements */
            $elements = $this->orderedClassElementsFixerWrapper->getElements($tokens, $index);

            if (\count($elements) === 0) {
                continue;
            }

            /** @var array<array<string>> $elements */
            $elements = $this->orderedClassElementsFixerWrapper->sortElements($elements);
            $sorted = $this->sortElements($elements);

            $endIndex = $elements[\count($elements) - 1]['end'];
            \assert(\is_int($endIndex));

            if ($sorted !== $elements) {
                $this->orderedClassElementsFixerWrapper->sortTokens($tokens, $index, $endIndex, $sorted);
            }

            $index = $endIndex;
        }
    }

    /**
     * @param array<array<string>> $elements
     */
    private function sortElements(array $elements): array
    {
        \usort(
            $elements,
            /**
             * @param array<string> $a
             * @param array<string> $b
             */
            static function (array $a, array $b): int {
                if (
                    $a['type'] === 'method' && $a['visibility'] === 'public'
                    && $b['type'] === 'method' && $b['visibility'] === 'public'
                    && isset($a['name'], $b['name'])
                ) {
                    if (!\in_array($a['name'], self::PUBLIC_METHODS_ORDER, true)) {
                        throw new \Exception(\sprintf('Method "%s" not in order list', $a['name']));
                    }
                    if (!\in_array($b['name'], self::PUBLIC_METHODS_ORDER, true)) {
                        throw new \Exception(\sprintf('Method "%s" not in order list', $b['name']));
                    }
                    foreach (self::PUBLIC_METHODS_ORDER as $name) {
                        if ($a['name'] === $name) {
                            return -1;
                        }
                        if ($b['name'] === $name) {
                            return 1;
                        }
                    }
                }

                if ($a['position'] === $b['position']) {
                    return $a['start'] <=> $b['start'];
                }

                return $a['position'] <=> $b['position'];
            }
        );

        return $elements;
    }
}
