<?php

/**
 * This file is part of contaoblackforest/contao-encore-bundle.
 *
 * (c) 2014-2019 The Contao Blackforest team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contaoblackforest/contao-encore-bundle
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2014-2019 The Contao Blackforest team.
 * @license    https://github.com/contaoblackforest/contao-encore-bundle/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace BlackForest\Contao\Encore\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This adds arguments from the definitions of webpack encore definitions.
 */
class AddArgumentsPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $builds = $container
            ->getDefinition('webpack_encore.entrypoint_lookup.cache_warmer')
            ->getArgument(0);

        $container
            ->getDefinition('cb.encore.table_layout_listener.encore_context_options')
            ->replaceArgument(0, $builds);

        $container
            ->getDefinition('cb.encore.frontend_listener.include_head_synthetic')
            ->replaceArgument(0, $builds);
    }
}
