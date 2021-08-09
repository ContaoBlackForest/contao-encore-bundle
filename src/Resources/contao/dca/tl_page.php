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

use BlackForest\Contao\Encore\Callback\Table\Page\FaviconConfigOptionsListener;
use BlackForest\Symfony\WebpackEncoreBundle\FaviconsWebpackBundle;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\Exception\PaletteNotFoundException;

if (in_array(FaviconsWebpackBundle::class, \Contao\System::getContainer()->getParameter('kernel.bundles'), true)) {

    try {
        // Contao >= 4.9
        PaletteManipulator::create()
            ->addLegend('website_legend', 'dns_legend', PaletteManipulator::POSITION_AFTER, false)
            ->addField(['faviconConfig'], 'website_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette('rootfallback', 'tl_page');
    } catch (PaletteNotFoundException $e) {
        // Contao < 4.9
        PaletteManipulator::create()
            ->addLegend('website_fallback_legend', 'dns_legend', PaletteManipulator::POSITION_AFTER, false)
            ->addField(['faviconConfig'], 'website_fallback_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette('root', 'tl_page');
    }

    /*
     * Add fields.
     */

    $GLOBALS['TL_DCA']['tl_page']['fields'] = array_merge(
        $GLOBALS['TL_DCA']['tl_page']['fields'],
        [
            'faviconConfig'    => [
                'label'             => &$GLOBALS['TL_LANG']['tl_page']['faviconConfig'],
                'exclude'           => true,
                'inputType'         => 'select',
                'eval'              => [
                    'includeBlankOption'    => true,
                    'chosen'                => true,
                    'tl_class'              => 'w50 m12'
                ],
                'options_callback'  => [FaviconConfigOptionsListener::class, '__invoke'],
                'sql'               => ['type' => 'string', 'length' => 255, 'notnull' => false, 'default' => null]
            ]
        ]
    );
}
