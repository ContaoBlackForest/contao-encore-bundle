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

namespace BlackForest\Contao\Encore\Test\Resources;

use PHPUnit\Framework\TestCase;

/**
 * @covers src/Resources/contao/dca/tl_layout.php
 */
class DataContainer extends TestCase
{
    public function testDcaLayout(): void
    {
        unset($GLOBALS['TL_DCA']);

        $GLOBALS['TL_DCA']['tl_layout'] = [
            'palettes' => [
                'default' => '',
                '__selector__' => []
            ],
            'fields' => []
        ];

        include \dirname(__DIR__, 2) . '/src/Resources/contao/dca/tl_layout.php';

        self::assertSame('{encore_legend:hide},useEncore', $GLOBALS['TL_DCA']['tl_layout']['palettes']);
        self::assertSame(['useEncore'], $GLOBALS['TL_DCA']['tl_layout']['palettes']['__selector__']);
        self::assertTrue(isset($GLOBALS['TL_DCA']['tl_layout']['fields']['useEncore']));
        self::assertTrue(isset($GLOBALS['TL_DCA']['tl_layout']['fields']['encoreConfig']));
        self::assertTrue(isset($GLOBALS['TL_DCA']['tl_layout']['subpalettes']['useEncore']));
        self::assertSame('encoreConfig', $GLOBALS['TL_DCA']['tl_layout']['subpalettes']['useEncore']);

        unset($GLOBALS['TL_DCA']);
    }
}
