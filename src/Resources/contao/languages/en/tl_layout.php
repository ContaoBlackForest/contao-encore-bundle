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

use BlackForest\Contao\Encore\Helper\EncoreConstants;

/*
 * The translation for tl_layout.
 */

/*
 * Fields
 */
$GLOBALS['TL_LANG']['tl_layout']['useEncore'][0]        = 'Use encore';
$GLOBALS['TL_LANG']['tl_layout']['useEncore'][1]        = 'Enable Encore to use this in this layout.';
$GLOBALS['TL_LANG']['tl_layout']['encoreConfig'][0]     = 'Configuration';
$GLOBALS['TL_LANG']['tl_layout']['encoreConfig'][1]     = 'Here you can make the settings for Encore.';
$GLOBALS['TL_LANG']['tl_layout']['encoreContext'][0]    = 'Context';
$GLOBALS['TL_LANG']['tl_layout']['encoreContext'][1]    = 'Here you can choose the context you want to use.';
$GLOBALS['TL_LANG']['tl_layout']['encoreSection'][0]    = 'Section';
$GLOBALS['TL_LANG']['tl_layout']['encoreSection'][1]    = 'Here you can select the section in which the context is used.';
$GLOBALS['TL_LANG']['tl_layout']['encoreInsertMode'][0] = 'Insert mode';
$GLOBALS['TL_LANG']['tl_layout']['encoreInsertMode'][1] = 'Here you can set how the context is to be inserted in the area.';

/*
 * Field options
 */
$GLOBALS['TL_LANG']['tl_layout']['encoreContext']['options']    = [
    EncoreConstants::SECTION_USER_CSS   => 'CSS combine section',
    EncoreConstants::SECTION_JAVASCRIPT => 'Javascript combine section',
    EncoreConstants::SECTION_JQUERY     => 'JQuery section',
    EncoreConstants::SECTION_MOOTOOLS   => 'MooTools section',
    EncoreConstants::SECTION_HEAD       => 'Head section',
    EncoreConstants::SECTION_BODY       => 'Body section'
];
$GLOBALS['TL_LANG']['tl_layout']['encoreInsertMode']['options'] = [
    EncoreConstants::APPEND  => 'Insert at the end',
    EncoreConstants::PREPEND => 'Insert at the start'
];

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_layout']['encore_legend'] = 'Encore settings';
