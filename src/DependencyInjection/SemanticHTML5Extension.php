<?php

declare(strict_types=1);

/*
 * This file is part of [menatwork/semantic_html5].
 *
 * (c) MEN AT WORK Werbeagentur GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace SemanticHTML5\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SemanticHTML5Extension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
