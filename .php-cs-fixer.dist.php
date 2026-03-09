<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/bin',
    ]);

$config = new PhpCsFixer\Config();

$config
    ->setFinder($finder)
    ->setCacheFile('.php-cs-fixer.cache')
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP80Migration:risky' => true,
        '@PHP81Migration' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'self',
        ],
        'php_unit_strict' => false,
        'php_unit_internal_class' => false,
        'php_unit_test_class_requires_covers' => false,
        'self_accessor' => false,
        'final_public_method_for_abstract_class' => true,
        'native_function_invocation' => false,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'single_line_throw' => false,
        'no_trailing_whitespace_in_string' => false,
        'escape_implicit_backslashes' => false,
        'explicit_string_variable' => false,
        'single_import_per_statement' => false,
        'group_import' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => null,
            'import_functions' => null,
        ],
        'braces' => [
            'allow_single_line_anonymous_class_with_empty_body' => true,
            'allow_single_line_closure' => true,
        ],
        'trailing_comma_in_multiline' => [
            'elements' => ['arguments', 'arrays', 'match', 'parameters'],
        ],
        'yoda_style' => [
            'equal' => null,
            'identical' => null,
            'less_and_greater' => null,
        ],
        'no_break_comment' => false,
        'single_line_comment_style' => [
            'comment_types' => ['hash'],
        ],
        'comment_to_phpdoc' => false,
        'no_superfluous_phpdoc_tags' => [
            'allow_unused_params' => false,
        ],
        'phpdoc_no_package' => true,
        'phpdoc_types_order' => false,
        'phpdoc_align' => false,
        'phpdoc_to_comment' => false,
        'increment_style' => false,
        'concat_space' => ['spacing' => 'one'],
        'operator_linebreak' => false,
        'date_time_immutable' => true,
        'method_chaining_indentation' => false,
        'blank_line_before_statement' => [
            'statements' => [
                'continue',
                'declare',
                'do',
                'exit',
                'for',
                'foreach',
                'goto',
                'if',
                'include',
                'include_once',
                'require',
                'require_once',
                'return',
                'switch',
                'throw',
                'try',
                'while',
                'yield',
                'yield_from',
            ],
        ],
        'return_assignment' => false,
        'explicit_indirect_variable' => false,
        'declare_strict_types' => false,
    ]);

return $config;
