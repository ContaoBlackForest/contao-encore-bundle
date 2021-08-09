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

namespace BlackForest\Contao\Encore\Test;

use BlackForest\Contao\Encore\BlackForestContaoEncoreBundle;
use BlackForest\Contao\Encore\DependencyInjection\Compiler\AddWebpackArgumentsPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \BlackForest\Contao\Encore\BlackForestContaoEncoreBundle
 */
class BlackForestContaoEncoreBundleTest extends TestCase
{
    public function testBundle()
    {
        $container = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();

        $container
            ->expects($this->once())
            ->method('addCompilerPass')
            ->withConsecutive(
                [$this->isInstanceOf(AddWebpackArgumentsPass::class), PassConfig::TYPE_BEFORE_OPTIMIZATION]
            )
            ->willReturn($container);

        $bundle = new BlackForestContaoEncoreBundle();
        $bundle->build($container);
    }
}
