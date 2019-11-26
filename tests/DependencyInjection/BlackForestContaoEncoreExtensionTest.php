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
use BlackForest\Contao\Encore\Hook\Frontend\AbstractIncludeSection;
use BlackForest\Contao\Encore\Hook\Frontend\IncludeBodySectionListener;
use BlackForest\Contao\Encore\Hook\Frontend\IncludeCSSCombineSectionListener;
use BlackForest\Contao\Encore\Hook\Frontend\IncludeHeadSectionListener;
use BlackForest\Contao\Encore\Hook\Frontend\IncludeJavascriptCombineSectionListener;
use BlackForest\Contao\Encore\Hook\Frontend\IncludeJQuerySectionListener;
use BlackForest\Contao\Encore\Hook\Frontend\IncludeMooToolsSectionListener;
use BlackForest\Contao\Encore\Callback\Table\Layout\EncoreContextOptionsListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
            AbstractIncludeSection::class,
            IncludeCSSCombineSectionListener::class,
            IncludeJavascriptCombineSectionListener::class,
            IncludeJQuerySectionListener::class,
            IncludeMooToolsSectionListener::class,
            IncludeHeadSectionListener::class,
            IncludeBodySectionListener::class,
            EncoreContextOptionsListener::class
        ];

        self::assertCount(\count($exceptedDefinitions), $container->getDefinitions());

        foreach ($exceptedDefinitions as $exceptedDefinitionName) {
            self::assertTrue($container->hasDefinition($exceptedDefinitionName));
        }
    }
}
