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

/*
 * The table configuration for tl_layout.
 */

use BlackForest\Contao\Encore\Callback\Table\Layout\EncoreContextOptionsListener;
use BlackForest\Contao\Encore\Helper\EncoreConstants;
use Contao\CoreBundle\DataContainer\PaletteManipulator;

/*
 * Add sub palettes.
 */

$GLOBALS['TL_DCA']['tl_layout']['palettes']['__selector__'][] = 'useEncore';
$GLOBALS['TL_DCA']['tl_layout']['subpalettes']['useEncore']   = '';


/*
 * Register fields to palettes.
 */

PaletteManipulator::create()
    ->addLegend('encore_legend', 'style_legend', PaletteManipulator::POSITION_BEFORE, true)
    ->addField(['useEncore'], 'encore_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_layout')
        ::create()
            ->addField(['encoreConfig'], 'useEncore', PaletteManipulator::POSITION_APPEND)
            ->applyToSubpalette('useEncore', 'tl_layout');


/*
 * Add fields.
 */

$GLOBALS['TL_DCA']['tl_layout']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_layout']['fields'],
    [
        'useEncore'    => [
            'label'     => &$GLOBALS['TL_LANG']['tl_layout']['useEncore'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => [
                'submitOnChange' => true,
                'tl_class'       => 'w50 m12'
            ],
            'sql'       => "char(1) NOT NULL default ''"
        ],
        'encoreConfig' => [
            'label'     => &$GLOBALS['TL_LANG']['tl_layout']['encoreConfig'],
            'exclude'   => true,
            'inputType' => 'multiColumnWizard',
            'eval'      => [
                'columnFields' => [
                    'context' => [
                        'label'            => &$GLOBALS['TL_LANG']['tl_layout']['encoreContext'],
                        'exclude'          => true,
                        'inputType'        => 'select',
                        'eval'             => [
                            'mandatory'          => true,
                            'includeBlankOption' => true,
                            'style'              => 'width:100%'
                        ],
                        'options_callback' => [EncoreContextOptionsListener::class, '__invoke']
                    ],
                    'section' => [
                        'label'            => &$GLOBALS['TL_LANG']['tl_layout']['encoreSection'],
                        'exclude'          => true,
                        'inputType'        => 'select',
                        'options'          => [
                            EncoreConstants::SECTION_USERCSS,
                            EncoreConstants::SECTION_JAVASCRIPT,
                            EncoreConstants::SECTION_JQUERY,
                            EncoreConstants::SECTION_MOOTOOLS,
                            EncoreConstants::SECTION_HEAD,
                            EncoreConstants::SECTION_BODY
                        ],
                        'reference'               => &$GLOBALS['TL_LANG']['tl_layout']['encoreContext']['options'],
                        'eval'             => [
                            'mandatory'          => true,
                            'includeBlankOption' => true,
                            'style'              => 'width:100%'
                        ]
                    ],
                    'insertMode' => [
                        'label'            => &$GLOBALS['TL_LANG']['tl_layout']['encoreInsertMode'],
                        'exclude'          => true,
                        'inputType'        => 'select',
                        'options'          => [
                            EncoreConstants::APPEND,
                            EncoreConstants::PREPEND
                        ],
                        'reference'               => &$GLOBALS['TL_LANG']['tl_layout']['encoreInsertMode']['options'],
                        'eval'             => [
                            'mandatory'          => true,
                            'style'              => 'width:100%'
                        ]
                    ]
                ]
            ],
            'sql'       => 'blob NULL'
        ]
    ]
);
