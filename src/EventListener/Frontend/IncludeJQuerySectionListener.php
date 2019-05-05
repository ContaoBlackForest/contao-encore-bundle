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

namespace BlackForest\Contao\Encore\EventListener\Frontend;

use BlackForest\Contao\Encore\Helper\EncoreConstants;

/**
 * This listener is for include the encore context in the jquery section.
 */
final class IncludeJQuerySectionListener implements GetAssetAsHtmlTagInterface, FilterAssetsForJavascriptInterface
{
    use IncludeSectionTrait;

    /**
     * The include section name.
     *
     * @var string
     */
    protected $includeSectionName = EncoreConstants::SECTION_JQUERY;
}
