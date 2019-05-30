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

namespace BlackForest\Contao\Encore\Test\DependencyInjection;

use BlackForest\Contao\Encore\DependencyInjection\BlackForestContaoEncoreExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @covers \BlackForest\Contao\Encore\DependencyInjection\BlackForestContaoEncoreExtension
 */
class BlackForestContaoEncoreExtensionTest extends TestCase
{
    public function testExtension(): void
    {
        $container = new ContainerBuilder();
        $extension = new BlackForestContaoEncoreExtension();

        $extension->load([], $container);

        $exceptedDefinitions = [
            'service_container',
            'cb.encore.frontend_listener.include_head_synthetic',
            'cb.encore.frontend_listener.include_css_combine_section',
            'cb.encore.frontend_listener.include_javascript_combine_section',
            'cb.encore.frontend_listener.include_jquery_section',
            'cb.encore.frontend_listener.include_mootools_section',
            'cb.encore.frontend_listener.include_head_section',
            'cb.encore.frontend_listener.include_body_section',
            'cb.encore.table_layout_listener.encore_context_options',
        ];

        $this->assertCount(\count($exceptedDefinitions), $container->getDefinitions());

        foreach ($exceptedDefinitions as $exceptedDefinitionName) {
            $this->assertTrue($container->hasDefinition($exceptedDefinitionName));
        }
    }
}
