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

declare(strict_types=1);

namespace BlackForest\Contao\Encore\ContaoManager;

use BlackForest\Contao\Encore\BlackForestContaoEncoreBundle;
use BlackForest\Symfony\WebpackEncoreBundle\FaviconsWebpackBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;

/**
 * Contao Manager plugin.
 */
final class Plugin implements BundlePluginInterface, ExtensionPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(WebpackEncoreBundle::class)->setLoadAfter([FrameworkBundle::class]),
            BundleConfig::create(FaviconsWebpackBundle::class)->setLoadAfter([FrameworkBundle::class]),
            BundleConfig::create(BlackForestContaoEncoreBundle::class)
                ->setLoadAfter(
                    [
                        ContaoCoreBundle::class,
                        WebpackEncoreBundle::class,
                        FaviconsWebpackBundle::class
                    ]
                )
        ];
    }

    /**
     * Configure the webpack encore extension, if is not configured.
     *
     * {@inheritDoc}
     */
    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container): array
    {
        $extensionConfigs = $this->predefineWebPackOutputPath($extensionName, $extensionConfigs);
        $extensionConfigs = $this->predefineJsonManifestPath($extensionName, $extensionConfigs);

        return $extensionConfigs;
    }

    /**
     * Predefine the webpack output, if is not configure.
     *
     * @param string $extensionName    The extension name.
     * @param array  $extensionConfigs The extension configuration.
     *
     * @return array
     */
    private function predefineWebPackOutputPath(string $extensionName, array $extensionConfigs): array
    {
        if ('webpack_encore' !== $extensionName) {
            return $extensionConfigs;
        }

        $addOutputPath = true;
        foreach ($extensionConfigs as $config) {
            if (!isset($config['output_path'])) {
                $addOutputPath = true;
                continue;
            }

            $addOutputPath = false;
            break;
        }
        if (false === $addOutputPath) {
            return $extensionConfigs;
        }

        $extensionConfigs[] = ['output_path' => '%kernel.project_dir%/web/layout'];
        return $extensionConfigs;
    }

    /**
     * Predefine the framework asset json path, if is not configure.
     *
     * @param string $extensionName    The extension name.
     * @param array  $extensionConfigs The extension configuration.
     *
     * @return array
     */
    private function predefineJsonManifestPath(string $extensionName, array $extensionConfigs): array
    {
        if ('framework' !== $extensionName) {
            return $extensionConfigs;
        }

        $addJsonManifestPath = true;
        foreach ($extensionConfigs as $config) {
            if (!isset($config['assets']['json_manifest_path'])) {
                $addJsonManifestPath = true;
                continue;
            }

            $addJsonManifestPath = false;
            break;
        }
        if (false === $addJsonManifestPath) {
            return $extensionConfigs;
        }

        $extensionConfigs[]['assets']['json_manifest_path'] = '%kernel.project_dir%/web/layout/manifest.json';
        return $extensionConfigs;
    }
}
