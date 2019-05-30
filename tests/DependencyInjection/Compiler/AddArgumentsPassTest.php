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

namespace BlackForest\Contao\Encore\Test\DependencyInjection\Compiler;

use BlackForest\Contao\Encore\DependencyInjection\BlackForestContaoEncoreExtension;
use BlackForest\Contao\Encore\DependencyInjection\Compiler\AddArgumentsPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\WebpackEncoreBundle\DependencyInjection\WebpackEncoreExtension;

/**
 * @covers \BlackForest\Contao\Encore\DependencyInjection\Compiler\AddArgumentsPass
 */
class AddArgumentsPassTest extends TestCase
{
    public function testAddArgumentPass(): void
    {
        $container = new ContainerBuilder();
        $container->prependExtensionConfig('webpack_encore', ['output_path' => 'foo']);

        $encoreExtension = new WebpackEncoreExtension();
        $encoreExtension->load([['output_path' => \dirname(__DIR__, 2) . '/Fixtures/build']], $container);

        $extension = new BlackForestContaoEncoreExtension();
        $extension->load([], $container);

        $compiler = new AddArgumentsPass();
        $compiler->process($container);

        $expected = [
            '_default' => \dirname(__DIR__, 2) . '/Fixtures/build/entrypoints.json'
        ];

        $definitions = [
            'cb.encore.table_layout_listener.encore_context_options',
            'cb.encore.frontend_listener.include_head_synthetic'
        ];

        foreach ($definitions as $id) {
            $definition = $container->getDefinition($id);

            $this->assertSame($expected, $definition->getArgument(0));
        }
    }
}
