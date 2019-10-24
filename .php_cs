<?php

declare(strict_types = 1);

require_once __DIR__ . '/dev-tools/vendor/autoload.php';

return PhpCsFixer\Config::create()
    ->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers())
    ->registerCustomFixers(new PhpCsFixerCustomFixersDev\Fixers())
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->files()
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/dev-tools/src')
            ->in(__DIR__ . '/tests')
            ->notName('php-cs-fixer.config.*.php')
            ->append([
                __FILE__,
                __DIR__ . '/dev-tools/readme',
            ])
    )
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP71Migration' => true,
        '@PHP71Migration:risky' => true,
        '@PHPUnit60Migration:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'align_multiline_comment' => [
            'comment_type' => 'all_multiline',
        ],
        'array_indentation' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'binary_operator_spaces' => true,
        'class_attributes_separation' => ['elements' => ['method', 'property']],
        'class_definition' => [
            'multiLineExtendsEachSingleLine' => true,
            'singleItemSingleLine' => true,
        ],
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'comment_to_phpdoc' => true,
        'compact_nullable_typehint' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
        'declare_equal_normalize' => [
            'space' => 'single',
        ],
        'explicit_indirect_variable' => true,
        'explicit_string_variable' => true,
        'final_class' => true,
        'final_internal_class' => true,
        'fully_qualified_strict_types' => true,
        'function_to_constant' => [
            'functions' => ['get_class', 'get_called_class', 'php_sapi_name', 'phpversion', 'pi'],
        ],
        'heredoc_to_nowdoc' => true,
        'increment_style' => [
            'style' => 'post',
        ],
        'linebreak_after_opening_tag' => true,
        'list_syntax' => [
            'syntax' => 'long',
        ],
        'logical_operators' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'method_chaining_indentation' => true,
        'multiline_comment_opening_closing' => true,
        'multiline_whitespace_before_semicolons' => true,
        'native_function_invocation' => [
            'include' => ['@all'],
        ],
        'no_alternative_syntax' => true,
        'no_binary_string' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'continue',
                'curly_brace_block',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'throw',
                'use',
                'use_trait',
            ],
        ],
        'no_null_property_initialization' => true,
        'no_php4_constructor' => true,
        'no_superfluous_elseif' => true,
        'no_unreachable_default_argument_value' => true,
        'no_unset_on_property' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_imports' => [
            'sortAlgorithm' => 'alpha',
        ],
        'php_unit_internal_class' => true,
        'php_unit_ordered_covers' => true,
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_strict' => true,
        'php_unit_test_annotation' => true,
        'php_unit_test_case_static_method_calls' => true,
        'php_unit_test_class_requires_covers' => true,
        'phpdoc_order' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types_order' => true,
        'return_assignment' => true,
        'static_lambda' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'string_line_ending' => true,
        'unary_operator_spaces' => false,
        'visibility_required' => [
            'elements' => [
                'const',
                'property',
                'method',
            ],
        ],
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
    ] + \array_reduce(
        \iterator_to_array(new PhpCsFixerCustomFixers\Fixers()),
        static function (array $carry, PhpCsFixer\Fixer\DefinedFixerInterface $fixer): array {
            if ($fixer instanceof PhpCsFixerCustomFixers\Fixer\NoReferenceInFunctionDefinitionFixer) {
                return $carry;
            }

            if (!$fixer instanceof PhpCsFixer\Fixer\DeprecatedFixerInterface) {
                $carry[$fixer->getName()] = true;
            }

            if ($fixer instanceof PhpCsFixerCustomFixers\Fixer\PhpdocOnlyAllowedAnnotationsFixer) {
                $carry[$fixer->getName()] = ['elements' => [
                    'covers',
                    'dataProvider',
                    'deprecated',
                    'internal',
                    'param',
                    'return',
                    'var',
                ]];
            }

            return $carry;
        },
        []
    ) + \array_reduce(
        \iterator_to_array(new PhpCsFixerCustomFixersDev\Fixers()),
        static function (array $carry, PhpCsFixer\Fixer\FixerInterface $fixer): array {
            $carry[$fixer->getName()] = true;

            return $carry;
        },
        []
    ));
