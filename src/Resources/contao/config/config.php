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

use BlackForest\Contao\Encore\EventListener\Frontend\IncludeBodySectionListener;
use BlackForest\Contao\Encore\EventListener\Frontend\IncludeCSSCombineSectionListener;
use BlackForest\Contao\Encore\EventListener\Frontend\IncludeHeadSectionListener;
use BlackForest\Contao\Encore\EventListener\Frontend\IncludeJavascriptCombineSectionListener;
use BlackForest\Contao\Encore\EventListener\Frontend\IncludeJQuerySectionListener;
use BlackForest\Contao\Encore\EventListener\Frontend\IncludeMooToolsSectionListener;


/*
 * Hooks.
 */

$GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'][] = [IncludeCSSCombineSectionListener::class, '__invoke'];
$GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'][] = [IncludeJavascriptCombineSectionListener::class, '__invoke'];
$GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'][] = [IncludeJQuerySectionListener::class , '__invoke'];
$GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'][] = [IncludeMooToolsSectionListener::class, '__invoke'];
$GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'][] = [IncludeHeadSectionListener::class, '__invoke'];
$GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'][] = [IncludeBodySectionListener::class, '__invoke'];
