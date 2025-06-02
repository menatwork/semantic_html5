<?php

declare(strict_types=1);

/*
 * This file is part of [menatwork/semantic_html5].
 *
 * (c) MEN AT WORK Werbeagentur GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace SemanticHTML5\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use SemanticHTML5\SemanticHTML5Bundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(SemanticHTML5Bundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
