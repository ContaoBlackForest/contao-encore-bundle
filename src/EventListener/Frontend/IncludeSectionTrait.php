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
use Contao\LayoutModel;
use Contao\PageModel;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookup;
use Symfony\WebpackEncoreBundle\Exception\EntrypointNotFoundException;
use Symfony\WebpackEncoreBundle\Twig\EntryFilesTwigExtension;

/**
 * This trait has the method's, for include the encore context in the section.
 */
trait IncludeSectionTrait
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
     * The entry files twig extension.
     *
     * @var EntryFilesTwigExtension
     */
    private $twigExtension;

    /**
     * The constructor.
     *
     * @param array                   $builds        The webpack encore builds with the entry point path.
     * @param CacheItemPoolInterface  $cache         The cache pool who have the entry point configuration.
     * @param EntryFilesTwigExtension $twigExtension The entry files twig extension.
     */
    public function __construct(array $builds, CacheItemPoolInterface $cache, EntryFilesTwigExtension $twigExtension)
    {
        $this->builds        = $builds;
        $this->cache         = $cache;
        $this->twigExtension = $twigExtension;
    }

    /**
     * Include the context in the section.
     *
     * @param string $buffer The buffer.
     *
     * @return string
     */
    public function __invoke(string $buffer): string
    {
        if (!($layout = $this->getPageLayout())
            || !$layout->useEncore
            || !$layout->encoreConfig
        ) {
            return $buffer;
        }

        $encoreConfig = \is_array($layout->encoreConfig)
            ? $layout->encoreConfig
            : \unserialize($layout->encoreConfig, ['allowed_classes' => false]);

        if (!\count($encoreConfig)
            || !($filteredConfig = $this->filterConfigBySection($encoreConfig))
        ) {
            return $buffer;
        }

        $this->addAssetsToSection($filteredConfig);

        return $buffer;
    }

    /**
     * Add the assets from the encore config to the section.
     *
     * @param array $config The encore config.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function addAssetsToSection(array $config): void
    {
        $prependAssets = [[]];
        $appendAssets  = [[]];
        foreach ($config as $item) {
            if (2 !== \substr_count($item['context'], '::')) {
                continue;
            }

            [$buildKey, $entryPoint, $type] = \explode('::', $item['context']);
            if (!\array_key_exists($buildKey, $this->builds)) {
                continue;
            }

            if (!$this->cache()->getItem($buildKey)->get()) {
                // Warm up the cache if is not exists.
                $this->cacheWarmUp($this->builds[$buildKey], $this->cache(), $buildKey);
            }

            if (!$this->cache()->getItem($buildKey)->get()) {
                continue;
            }

            if (EncoreConstants::PREPEND === $item['insertMode']) {
                $prependAssets[] = $this->getWebPackAssets($buildKey, $entryPoint, $type);

                continue;
            }

            $appendAssets[] = $this->getWebPackAssets($buildKey, $entryPoint, $type);
        }
        $prependAssets = \array_filter(\array_merge(...$prependAssets));
        $appendAssets  = \array_filter(\array_merge(...$appendAssets));

        if (!\count($prependAssets) && !\count($appendAssets)) {
            return;
        }

        $GLOBALS[$this->includeSectionName()] =
            \array_merge(
                $prependAssets,
                ($GLOBALS[$this->includeSectionName()] ?? []),
                $appendAssets
            );
    }

    /**
     * Get the webpack assets.
     *
     * @param string $buildKey   The build key.
     * @param string $entryPoint The entry point.
     * @param string $type       The asset type.
     *
     * @return array
     */
    private function getWebPackAssets(string $buildKey, string $entryPoint, string $type): array
    {
        $assets = [];
        switch ($type) {
            case 'css':
                if ($this instanceof GetAssetAsFileInterface) {
                    $assets = $this->twigExtension()->getWebpackCssFiles($entryPoint, $buildKey);
                } else {
                    $assets = $this->twigExtension()->renderWebpackLinkTags($entryPoint, null, $buildKey);
                }

                break;

            case 'js':
                if ($this instanceof GetAssetAsFileInterface) {
                    $assets = $this->twigExtension()->getWebpackJsFiles($entryPoint, $buildKey);
                } else {
                    $assets = $this->twigExtension()->renderWebpackScriptTags($entryPoint, null, $buildKey);
                }

                break;

            default:
        }

        if (!($this instanceof GetAssetAsFileInterface)) {
            return [$assets];
        }

        return \array_map(
            function ($file) {
                if (0 === \strpos($file, '/')) {
                    return \substr($file, 1) . '|static';
                }

                // If the encore dev server started, not add the static flag.
                return (\filter_var($file, FILTER_VALIDATE_URL)) ? $file : $file . '|static';
            },
            $assets
        );
    }

    /**
     * Filter the config by the section.
     *
     * @param array $config The config.
     *
     * @return array
     */
    private function filterConfigBySection(array $config): array
    {
        return \array_filter(
            $config,
            function ($item) {
                // Filter if the item is in the declared section.
                return (($this->includeSectionName() === $item['section'])
                        // Filter for javascript item.
                        && ((($this instanceof FilterAssetsForJavascriptInterface)
                             && ('::js' === \substr($item['context'], -\strlen('::js'))))
                            // Filter for css item.
                            || (($this instanceof FilterAssetsForCssInterface)
                                && ('::css' === \substr($item['context'], -\strlen('::css')))))
                );
            }
        );
    }

    /**
     * Get the page layout.
     *
     * @return LayoutModel|\Contao\Model\Collection|PageModel|null
     */
    private function getPageLayout()
    {
        if (!($page = $this->getAcivePage())
            || !($layout = $page->getRelated('layout'))
        ) {
            return null;
        }

        return $layout;
    }

    /**
     * Get the active page.
     *
     * @return PageModel|null
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function getAcivePage()
    {
        return ($GLOBALS['objPage'] ?? null);
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

    /**
     * The cache pool who have the entry point configuration.
     *
     * @return CacheItemPoolInterface
     */
    private function cache(): CacheItemPoolInterface
    {
        return $this->cache;
    }

    /**
     * The include section name.
     *
     * @return EntryFilesTwigExtension
     */
    private function includeSectionName(): string
    {
        return $this->includeSectionName;
    }

    /**
     * The entry files twig extension.
     *
     * @return EntryFilesTwigExtension
     */
    private function twigExtension(): EntryFilesTwigExtension
    {
        return $this->twigExtension;
    }
}
