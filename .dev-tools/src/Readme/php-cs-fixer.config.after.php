<?php
return (new PhpCsFixer\Config)
    ->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers())
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        PhpCsFixerCustomFixers\Fixer\NoLeadingSlashInGlobalNamespaceFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\PhpdocNoSuperfluousParamFixer::name() => true,
    ]);
