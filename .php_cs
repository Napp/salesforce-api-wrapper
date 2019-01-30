<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('tests')
    ->exclude('vendor')
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        'psr4' => true,
        'concat_space' => ['spacing' => 'one'],
        'no_empty_statement' => true,
        'elseif' => true,
        'no_extra_consecutive_blank_lines' => true,
        'line_ending' => true,
        'ordered_imports' => true,
        'no_spaces_inside_parenthesis' => true,
        'no_leading_import_slash' => true,
        'no_extra_consecutive_blank_lines' => ['use'],
        'blank_line_before_return' => true,
        'full_opening_tag' => true,
        'array_syntax' => ['syntax' => 'short'],
        'trim_array_spaces' => true,
        'no_trailing_whitespace' => true,
        'unary_operator_spaces' => true,
        'no_unused_imports' => true,
        'no_whitespace_in_blank_line' => true,
    ]);