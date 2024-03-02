<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__.'/src', __DIR__.'/tests'])
    ->notPath('Automate/Plugin/AbstractNotificationPlugin.php')
    ->notPath('Automate/Plugin/CacheToolPlugin.php')
    ->notPath('Automate/Plugin/GitlabPlugin.php')
    ->notPath('Automate/Plugin/GitterPlugin.php')
    ->notPath('Automate/Plugin/SentryPlugin.php')
    ->notPath('Automate/Plugin/SlackPlugin.php')
    ->notPath('Automate/Configuration.php');

return (new PhpCsFixer\Config())
    ->setRules(
        [
            '@PSR1' => true,
            '@PSR2' => true,
            '@Symfony' => true,
            'phpdoc_order' => true,
            'general_phpdoc_annotation_remove' => ['annotations' => ["author", "package"]],
            'align_multiline_comment' => true,
            'combine_consecutive_issets' => true,
            'combine_consecutive_unsets' => true,
            'compact_nullable_typehint' => true,
            'linebreak_after_opening_tag' => true,
            'method_chaining_indentation' => true,
            'multiline_comment_opening_closing' => true,
            'multiline_whitespace_before_semicolons' => true,
            'no_superfluous_elseif' => true,
            'no_useless_else' => true,
            'no_useless_return' => true,
            'phpdoc_add_missing_param_annotation' => true,
            'phpdoc_types_order' => true,
        ]
    )
    ->setFinder($finder);