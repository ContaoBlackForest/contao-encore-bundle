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

namespace BlackForest\Contao\Encore\ContaoManager;

use BlackForest\Contao\Encore\BlackForestContaoEncoreBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;

/**
 * Contao Manager plugin.
 */
class Plugin implements BundlePluginInterface, ExtensionPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(WebpackEncoreBundle::class),
            BundleConfig::create(BlackForestContaoEncoreBundle::class)
                ->setLoadAfter(
                    [
                        ContaoCoreBundle::class,
                        WebpackEncoreBundle::class
                    ]
                )
        ];
    }

    /**
     * Configure the webpack encore extension, if is not configured.
     *
     * {@inheritDoc}
     */
    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container)
    {
        if ('webpack_encore' !== $extensionName || !empty($extensionConfigs)) {
            return $extensionConfigs;
        }

        $extensionConfigs = [
            [
                'output_path' => '%kernel.project_dir%/web/layout'
            ]
        ];

        return $extensionConfigs;
    }
}
