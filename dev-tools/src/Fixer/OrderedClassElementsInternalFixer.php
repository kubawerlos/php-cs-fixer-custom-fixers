<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixersDev\Fixer {
    use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
    use PhpCsFixer\Fixer\FixerInterface;
    use PhpCsFixer\Tokenizer\Tokens;

    final class OrderedClassElementsInternalFixer implements FixerInterface
    {
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
    function usort(array &$elements): void
    {
        \usort($elements, static function (array $a, array $b) {
            if ($a['type'] === 'method' && $a['visibility'] === 'public'
                && $b['type'] === 'method' && $b['visibility'] === 'public'
                && isset($a['name'], $b['name'])) {
                foreach ([
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
                ] as $name) {
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
        });
    }
}
