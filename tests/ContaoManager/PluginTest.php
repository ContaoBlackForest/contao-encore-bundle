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

namespace BlackForest\Contao\Encore\Test\ContaoManager;

use BlackForest\Contao\Encore\BlackForestContaoEncoreBundle;
use BlackForest\Contao\Encore\ContaoManager\Plugin;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;

/**
 * @covers \BlackForest\Contao\Encore\ContaoManager\Plugin
 */
class PluginTest extends TestCase
{
    public function testPlugin(): void
    {
        $parser = $this->createMock(ParserInterface::class);

        $config = $this->createMock(ConfigInterface::class);

        $config
            ->expects($this->exactly(2))
            ->method('getName')
            ->willReturn(WebpackEncoreBundle::class, BlackForestContaoEncoreBundle::class);

        $config
            ->expects($this->exactly(2))
            ->method('getReplace')
            ->willReturn([], []);

        $config
            ->expects($this->exactly(2))
            ->method('getLoadAfter')
            ->willReturn([FrameworkBundle::class], [ContaoCoreBundle::class, WebpackEncoreBundle::class]);

        $config
            ->expects($this->exactly(2))
            ->method('loadInProduction')
            ->willReturn(true, true);


        $config
            ->expects($this->exactly(2))
            ->method('loadInDevelopment')
            ->willReturn(true, true);


        $plugin  = new Plugin();
        $bundles = $plugin->getBundles($parser);

        foreach ($bundles as $bundle) {
            self::assertSame($config->getName(), $bundle->getName());
            self::assertSame($config->getReplace(), $bundle->getReplace());
            self::assertSame($config->getLoadAfter(), $bundle->getLoadAfter());
            self::assertSame($config->loadInProduction(), $bundle->loadInProduction());
            self::assertSame($config->loadInDevelopment(), $bundle->loadInDevelopment());
        }
    }

    public function testGetExtensionConfig(): void
    {
        $container = $this->createMock(ContainerBuilder::class);

        $plugin = new Plugin();

        static::assertSame([], $plugin->getExtensionConfig('foo', [], $container));
        static::assertSame(['foo'], $plugin->getExtensionConfig('foo', ['foo'], $container));
        static::assertSame(
            ['hasConfig', ['output_path' => 'configured']],
            $plugin->getExtensionConfig('webpack_encore', ['hasConfig', ['output_path' => 'configured']], $container)
        );
        static::assertSame(
            ['hasConfig', ['output_path' => '%kernel.project_dir%/web/layout']],
            $plugin->getExtensionConfig('webpack_encore', ['hasConfig'], $container)
        );
        static::assertSame(
            ['hasConfig', ['output_path' => '%kernel.project_dir%/web/layout']],
            $plugin->getExtensionConfig('webpack_encore', ['hasConfig'], $container)
        );
        static::assertSame(
            [['output_path' => '%kernel.project_dir%/web/layout']],
            $plugin->getExtensionConfig('webpack_encore', [], $container)
        );

        static::assertSame(
            [['assets' => ['json_manifest_path' => '%kernel.project_dir%/web/layout/manifest.json']]],
            $plugin->getExtensionConfig('framework', [], $container)
        );
        static::assertSame(
            [['assets' => ['json_manifest_path' => 'configured']]],
            $plugin->getExtensionConfig('framework', [['assets' => ['json_manifest_path' => 'configured']]], $container)
        );
    }
}
