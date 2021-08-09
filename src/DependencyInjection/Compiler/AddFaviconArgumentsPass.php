<?php

/**
 * This file is part of contaoblackforest/contao-encore-bundle.
 *
 * (c) 2014-2021 The Contao Blackforest team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contaoblackforest/contao-encore-bundle
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2014-2021 The Contao Blackforest team.
 * @license    https://github.com/contaoblackforest/contao-encore-bundle/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

declare(strict_types=1);

namespace BlackForest\Contao\Encore\DependencyInjection\Compiler;

use BlackForest\Contao\Encore\Callback\Table\Page\FaviconConfigOptionsListener;
use BlackForest\Symfony\WebpackEncoreBundle\CacheWarmer\FaviconsCacheWarmer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AddFaviconArgumentsPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $cacheKeys = $container
            ->getDefinition(FaviconsCacheWarmer::class)
            ->getArgument('$cacheKeys');

        $container
            ->getDefinition(FaviconConfigOptionsListener::class)
            ->setArgument('$cacheKeys', $cacheKeys);
    }
}
