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

namespace BlackForest\Contao\Encore\Callback\Table\Page;

use Contao\DataContainer;

final class FaviconConfigOptionsListener
{
    /**
     * The cache keys.
     *
     * @var array
     */
    private $cacheKeys;

    /**
     * The constructor.
     *
     * @param array $cacheKeys The cache keys.
     */
    public function __construct(array $cacheKeys)
    {
        $this->cacheKeys  = $cacheKeys;
    }

    /**
     * Collect the options for the encore context.
     *
     * @param DataContainer $container The data container.
     *
     * @return array
     */
    public function __invoke(DataContainer $container): array
    {
        if (!\count($this->cacheKeys)) {
            return [];
        }

        return \array_keys($this->cacheKeys);
    }
}
