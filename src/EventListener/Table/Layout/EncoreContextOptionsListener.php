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

namespace BlackForest\Contao\Encore\EventListener\Table\Layout;

use Contao\Widget;
use MenAtWork\MultiColumnWizardBundle\Contao\Widgets\MultiColumnWizard;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookup;
use Symfony\WebpackEncoreBundle\Exception\EntrypointNotFoundException;

/**
 * The listener is for collect the encore context options.
 */
class EncoreContextOptionsListener
{
    /**
     * The webpack encore builds with the entry point path.
     *
     * @var array
     */
    private $builds;

    /**
     * The cache pool who have the entry point configuration.
     *
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * The constructor.
     *
     * @param array                  $builds The webpack encore builds with the entry point path.
     * @param CacheItemPoolInterface $cache  The cache pool who have the entry point configuration.
     */
    public function __construct(array $builds, CacheItemPoolInterface $cache)
    {
        $this->builds = $builds;
        $this->cache  = $cache;
    }

    /**
     * Collect the options for the encore context.
     *
     * @param Widget|MultiColumnWizard $widget The widget.
     *
     * @return array
     */
    public function collect(Widget $widget): array
    {
        $columnFields = $widget->columnFields;
        if (!isset($columnFields['context'])) {
            return [];
        }

        $options = [[]];
        foreach (\array_keys($this->builds) as $buildKey) {
            if (!$this->cache->getItem($buildKey)->get()) {
                // Warm up the cache if is not exists.
                $this->cacheWarmUp($this->builds[$buildKey], $this->cache, $buildKey);
            }

            if (!($config = $this->cache->getItem($buildKey)->get())
                || !\array_key_exists('entrypoints', $config)
                || !\count($config['entrypoints'])
            ) {
                continue;
            }

            $options[] = $this->collectOptionsFromEntryPoints($config['entrypoints'], $buildKey);
        }

        return \array_merge(...$options);
    }

    /**
     * Collect the options from the entry points.
     *
     * @param array  $entryPoints The list of entry points.
     * @param string $buildKey    The build key.
     *
     * @return array
     */
    private function collectOptionsFromEntryPoints(array $entryPoints, string $buildKey): array
    {
        $options = [];

        $label = (('_default' === $buildKey) ? '' : $buildKey . '::') . '%s::%s';
        $value = $buildKey . '::%s::%s';
        foreach ($entryPoints as $entryPointName => $entryPointConfig) {
            if (isset($entryPointConfig['css'])) {
                $optionValue = \sprintf($value, $entryPointName, 'css');

                $options[$optionValue] = \sprintf($label, $entryPointName, 'css');
            }

            if (isset($entryPointConfig['js'])) {
                $optionValue = \sprintf($value, $entryPointName, 'js');

                $options[$optionValue] = \sprintf($label, $entryPointName, 'js');
            }
        }

        return $options;
    }

    /**
     * Cache warm up for has the entry points configuration in the cache.
     *
     * @param string                 $path     The file path to the entry point file.
     * @param CacheItemPoolInterface $cache    The cache.
     * @param string                 $buildKey The build key.
     *
     * @return void
     */
    private function cacheWarmUp(string $path, CacheItemPoolInterface $cache, string $buildKey): void
    {
        // If the file does not exist then just skip past this entry point.
        if (!\file_exists($path)) {
            return;
        }

        $entryPointLookup = new EntrypointLookup($path, $cache, $buildKey);

        // @codingStandardsIgnoreStart
        try {
            $entryPointLookup->getJavaScriptFiles('dummy');
        } catch (EntrypointNotFoundException $e) {
            // ignore exception
        }
        // @codingStandardsIgnoreEnd
    }
}
