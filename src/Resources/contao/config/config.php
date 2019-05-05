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
 * Hooks.
 */

$GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'][] =
    ['cb.encore.frontend_listener.include_css_combine_section', 'includeToSection'];

$GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'][] =
    ['cb.encore.frontend_listener.include_javascript_combine_section', 'includeToSection'];

$GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'][] =
    ['cb.encore.frontend_listener.include_jquery_section', 'includeToSection'];

$GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'][] =
    ['cb.encore.frontend_listener.include_mootools_section', 'includeToSection'];

$GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'][] =
    ['cb.encore.frontend_listener.include_head_section', 'includeToSection'];

$GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'][] =
    ['cb.encore.frontend_listener.include_body_section', 'includeToSection'];
