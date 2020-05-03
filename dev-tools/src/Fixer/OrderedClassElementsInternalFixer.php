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

namespace PhpCsFixerCustomFixersDev\Fixer {
    use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
    use PhpCsFixer\Fixer\FixerInterface;
    use PhpCsFixer\Tokenizer\Tokens;

    /**
     * @internal
     */
    final class OrderedClassElementsInternalFixer implements FixerInterface
    {
        public const PUBLIC_METHODS_ORDER = [
            'getDefinition',
            'getConfigurationDefinition',
            'configure',
            'getName',
            'getPriority',
            'getPullRequestId',
            'supports',
            'isCandidate',
            'isRisky',
            'fix',
            'getSuccessorsNames',
        ];

        /** @var OrderedClassElementsFixer */
        private $orderedClassElementsFixer;

        public function __construct()
        {
            $this->orderedClassElementsFixer = new OrderedClassElementsFixer();
        }

        public function getName(): string
        {
            return 'Internal/' . $this->orderedClassElementsFixer->getName();
        }

        public function getPriority(): int
        {
            return $this->orderedClassElementsFixer->getPriority();
        }

        public function supports(\SplFileInfo $file): bool
        {
            return $this->orderedClassElementsFixer->supports($file);
        }

        public function isCandidate(Tokens $tokens): bool
        {
            return $tokens->findSequence([[T_EXTENDS], [T_STRING, 'AbstractFixer']]) !== null;
        }

        public function isRisky(): bool
        {
            return $this->orderedClassElementsFixer->isRisky();
        }

        public function fix(\SplFileInfo $file, Tokens $tokens): void
        {
            $this->orderedClassElementsFixer->fix($file, $tokens);
        }
    }
}

namespace PhpCsFixer\Fixer\ClassNotation {
    use PhpCsFixerCustomFixersDev\Fixer\OrderedClassElementsInternalFixer;

    /**
     * @internal
     *
     * @param array<array<string>> $elements
     */
    function usort(array &$elements): void
    {
        \usort(
            $elements,
            /**
             * @param string[] $a
             * @param string[] $b
             */
            static function (array $a, array $b): int {
                if ($a['type'] === 'method' && $a['visibility'] === 'public'
                    && $b['type'] === 'method' && $b['visibility'] === 'public'
                    && isset($a['name'], $b['name'])) {
                    if (!\in_array($a['name'], OrderedClassElementsInternalFixer::PUBLIC_METHODS_ORDER, true)) {
                        throw new \Exception(\sprintf('Method "%s" not in order list', $a['name']));
                    }
                    if (!\in_array($b['name'], OrderedClassElementsInternalFixer::PUBLIC_METHODS_ORDER, true)) {
                        throw new \Exception(\sprintf('Method "%s" not in order list', $b['name']));
                    }
                    foreach (OrderedClassElementsInternalFixer::PUBLIC_METHODS_ORDER as $name) {
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
    }
}
