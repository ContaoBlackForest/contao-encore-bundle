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
$GLOBALS['TL_LANG']['tl_layout']['useEncore'][0]        = 'Encore aktivieren';
$GLOBALS['TL_LANG']['tl_layout']['useEncore'][1]        = 'Aktivieren Sie Encore um dies in diesem Layout zu benutzen.';
$GLOBALS['TL_LANG']['tl_layout']['encoreConfig'][0]     = 'Konfiguration';
$GLOBALS['TL_LANG']['tl_layout']['encoreConfig'][1]     = 'Hier können Sie die Einstellungen für Encore vornehmen.';
$GLOBALS['TL_LANG']['tl_layout']['encoreContext'][0]    = 'Kontext';
$GLOBALS['TL_LANG']['tl_layout']['encoreContext'][1]    = 'Hier können Sie den Kontext wählen den Sie benutzen möchten.';
$GLOBALS['TL_LANG']['tl_layout']['encoreSection'][0]    = 'Bereich';
$GLOBALS['TL_LANG']['tl_layout']['encoreSection'][1]    = 'Hier können Sie den Bereich wählen in dem der Kontext benutzt wird.';
$GLOBALS['TL_LANG']['tl_layout']['encoreInsertMode'][0] = 'Einfügemodus';
$GLOBALS['TL_LANG']['tl_layout']['encoreInsertMode'][1] = 'Hier können Sie einstellen wie der Kontext im Bereich eingefügt werden soll.';

/*
 * Field options
 */
$GLOBALS['TL_LANG']['tl_layout']['encoreContext']['options']    = [
    EncoreConstants::SECTION_USERCSS    => 'CSS kombinierter Bereich',
    EncoreConstants::SECTION_JAVASCRIPT => 'Javascript kombinierter Bereich',
    EncoreConstants::SECTION_JQUERY     => 'JQuery Bereich',
    EncoreConstants::SECTION_MOOTOOLS   => 'MooTools Bereich',
    EncoreConstants::SECTION_HEAD       => 'Head Bereich',
    EncoreConstants::SECTION_BODY       => 'Body Bereich'
];
$GLOBALS['TL_LANG']['tl_layout']['encoreInsertMode']['options'] = [
    EncoreConstants::APPEND  => 'Einfügen am Ende',
    EncoreConstants::PREPEND => 'Einfügen am Anfang'
];

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_layout']['encore_legend'] = 'Encore settings';
