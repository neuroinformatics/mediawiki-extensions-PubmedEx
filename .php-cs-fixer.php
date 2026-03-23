<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor');

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@Symfony' => true,
])
    ->setFinder($finder);
