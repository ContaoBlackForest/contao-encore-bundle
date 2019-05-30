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

namespace BlackForest\Contao\Encore\Test\EventListener\Frontend;

use BlackForest\Contao\Encore\EventListener\Frontend\IncludeBodySectionListener;
use BlackForest\Contao\Encore\EventListener\Frontend\IncludeCSSCombineSectionListener;
use BlackForest\Contao\Encore\EventListener\Frontend\IncludeHeadSectionListener;
use BlackForest\Contao\Encore\EventListener\Frontend\IncludeJavascriptCombineSectionListener;
use BlackForest\Contao\Encore\EventListener\Frontend\IncludeJQuerySectionListener;
use BlackForest\Contao\Encore\EventListener\Frontend\IncludeMooToolsSectionListener;
use BlackForest\Contao\Encore\Helper\EncoreConstants;
use Contao\Model;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookup;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupCollection;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Symfony\WebpackEncoreBundle\Asset\TagRenderer;
use Symfony\WebpackEncoreBundle\Twig\EntryFilesTwigExtension;

/**
 * Test for include by section.
 *
 * @covers \BlackForest\Contao\Encore\EventListener\Frontend\IncludeSectionTrait
 */
class IncludeSectionTest extends TestCase
{
    public function dataProviderIncludeBodySection(): array
    {
        return [
            // 0
            [false],
            // 1
            [true],
            // 2
            [true, true],
            // 3 Test with wrong context
            [
                true,
                true,
                true,
                [],
                [
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => 'app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => 'app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_not_exist::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_not_exist::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_no_build_key::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_no_build_key::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 4
            [
                true,
                true,
                true,
                [
                    '<link rel="stylesheet" href="/build/styles.css" integrity="sha384-4g+Zv0iELStVvA4/B27g4TQHUMwZttA5TEojjUyB8Gl5p7sarU4y+VTSGMrNab8n"><link rel="stylesheet" href="/build/styles2.css" integrity="sha384-hfZmq9+2oI5Cst4/F4YyS2tJAAYdGz7vqSMP8cJoa8bVOr2kxNRLxSw6P8UZjwUn">',
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 5
            [
                true,
                true,
                true,
                [
                    '<link rel="stylesheet" href="http://localhost:8080/build-dev/styles.css"><link rel="stylesheet" href="http://localhost:8080/build-dev/styles2.css">',
                    '<script src="http://localhost:8080/build-dev/app1.js"></script><script src="http://localhost:8080/build-dev/app2.js"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app-dev::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app-dev::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 6
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::other_entry::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 7
            [
                true,
                true,
                true,
                [
                    '<link rel="stylesheet" href="/build/styles.css" integrity="sha384-4g+Zv0iELStVvA4/B27g4TQHUMwZttA5TEojjUyB8Gl5p7sarU4y+VTSGMrNab8n"><link rel="stylesheet" href="/build/styles2.css" integrity="sha384-hfZmq9+2oI5Cst4/F4YyS2tJAAYdGz7vqSMP8cJoa8bVOr2kxNRLxSw6P8UZjwUn">',
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>',
                    '<script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 8
            [
                true,
                true,
                true,
                [
                    '<link rel="stylesheet" href="/build/styles.css" integrity="sha384-4g+Zv0iELStVvA4/B27g4TQHUMwZttA5TEojjUyB8Gl5p7sarU4y+VTSGMrNab8n"><link rel="stylesheet" href="/build/styles2.css" integrity="sha384-hfZmq9+2oI5Cst4/F4YyS2tJAAYdGz7vqSMP8cJoa8bVOr2kxNRLxSw6P8UZjwUn">',
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>',
                    '<script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 9
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>',
                    '<link rel="stylesheet" href="/build/styles.css" integrity="sha384-4g+Zv0iELStVvA4/B27g4TQHUMwZttA5TEojjUyB8Gl5p7sarU4y+VTSGMrNab8n"><link rel="stylesheet" href="/build/styles2.css" integrity="sha384-hfZmq9+2oI5Cst4/F4YyS2tJAAYdGz7vqSMP8cJoa8bVOr2kxNRLxSw6P8UZjwUn">',
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ]
                ]
            ],
            // 10
            [
                true,
                true,
                true,
                [
                    '<link rel="stylesheet" href="/build/styles.css" integrity="sha384-4g+Zv0iELStVvA4/B27g4TQHUMwZttA5TEojjUyB8Gl5p7sarU4y+VTSGMrNab8n"><link rel="stylesheet" href="/build/styles2.css" integrity="sha384-hfZmq9+2oI5Cst4/F4YyS2tJAAYdGz7vqSMP8cJoa8bVOr2kxNRLxSw6P8UZjwUn">'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataProviderIncludeBodySection
     *
     * @covers       \BlackForest\Contao\Encore\EventListener\Frontend\IncludeBodySectionListener
     */
    public function testIncludeBodySection(
        bool $hasPageLayut,
        bool $useEncore = false,
        bool $hasEncoreConfig = false,
        array $expected = [],
        array $encoreConfig = []
    ): void {
        unset($GLOBALS['TL_BODY']);

        $this->createActivePage($hasPageLayut, $useEncore, $encoreConfig);

        $cache          = new ArrayAdapter();
        $includeSection = new IncludeBodySectionListener(
            [
                '_default'   => \dirname(__DIR__, 2) . '/Fixtures/build/entrypoints.json',
                '_not_exist' => \dirname(__DIR__, 2) . '/Fixtures/build/_not_exist.json'
            ],
            $cache,
            $this->createTwigExtension($cache)
        );

        $includeSection->includeToSection('');

        if (!$hasPageLayut || !$useEncore || !$hasEncoreConfig) {
            $this->assertNull($cache->getItem('_default')->get());

            return;
        }

        if (!\count($expected)) {
            $this->assertArrayNotHasKey('TL_BODY', $GLOBALS);

            return;
        }

        $this->assertSame($expected, $GLOBALS['TL_BODY']);
        $this->assertSame('TL_BODY', EncoreConstants::SECTION_BODY);

        unset($GLOBALS['TL_BODY']);
    }

    public function dataProviderIncludeCSSCombineSection(): array
    {
        return [
            // 0
            [false],
            // 1
            [true],
            // 2
            [true, true],
            // 3 Test with wrong context
            [
                true,
                true,
                true,
                [],
                [
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => 'app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => 'app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_not_exist::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_not_exist::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_no_build_key::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_no_build_key::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 4
            [
                true,
                true,
                true,
                [
                    'build/styles.css|static',
                    'build/styles2.css|static'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 5
            [
                true,
                true,
                true,
                [
                    'http://localhost:8080/build-dev/styles.css',
                    'http://localhost:8080/build-dev/styles2.css'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app-dev::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app-dev::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 6
            [
                true,
                true,
                true,
                [],
                [
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::other_entry::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 7
            [
                true,
                true,
                true,
                [
                    'build/styles.css|static',
                    'build/styles2.css|static'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 8
            [
                true,
                true,
                true,
                [
                    'build/styles.css|static',
                    'build/styles2.css|static'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 9
            [
                true,
                true,
                true,
                [
                    'build/styles.css|static',
                    'build/styles2.css|static'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ]
                ]
            ],
            // 10
            [
                true,
                true,
                true,
                [
                    'build/styles.css|static',
                    'build/styles2.css|static'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataProviderIncludeCSSCombineSection
     *
     * @covers       \BlackForest\Contao\Encore\EventListener\Frontend\IncludeCSSCombineSectionListener
     */
    public function testIncludeCSSCombineSection(
        bool $hasPageLayut,
        bool $useEncore = false,
        bool $hasEncoreConfig = false,
        array $expected = [],
        array $encoreConfig = []
    ): void {
        unset($GLOBALS['TL_USER_CSS']);

        $this->createActivePage($hasPageLayut, $useEncore, $encoreConfig);

        $cache          = new ArrayAdapter();
        $includeSection = new IncludeCSSCombineSectionListener(
            [
                '_default'   => \dirname(__DIR__, 2) . '/Fixtures/build/entrypoints.json',
                '_not_exist' => \dirname(__DIR__, 2) . '/Fixtures/build/_not_exist.json'
            ],
            $cache,
            $this->createTwigExtension($cache)
        );

        $includeSection->includeToSection('');

        if (!$hasPageLayut || !$useEncore || !$hasEncoreConfig) {
            $this->assertNull($cache->getItem('_default')->get());

            return;
        }

        if (!\count($expected)) {
            $this->assertArrayNotHasKey('TL_USER_CSS', $GLOBALS);

            return;
        }

        $this->assertSame($expected, $GLOBALS['TL_USER_CSS']);
        $this->assertSame('TL_USER_CSS', EncoreConstants::SECTION_USERCSS);

        unset($GLOBALS['TL_USER_CSS']);
    }

    public function dataProviderIncludeHeadSection(): array
    {
        return [
            // 0
            [false],
            // 1
            [true],
            // 2
            [true, true],
            // 3 Test with wrong context
            [
                true,
                true,
                true,
                [],
                [
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => 'app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => 'app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_not_exist::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_not_exist::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_no_build_key::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_no_build_key::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 4
            [
                true,
                true,
                true,
                [
                    '<link rel="stylesheet" href="/build/styles.css" integrity="sha384-4g+Zv0iELStVvA4/B27g4TQHUMwZttA5TEojjUyB8Gl5p7sarU4y+VTSGMrNab8n"><link rel="stylesheet" href="/build/styles2.css" integrity="sha384-hfZmq9+2oI5Cst4/F4YyS2tJAAYdGz7vqSMP8cJoa8bVOr2kxNRLxSw6P8UZjwUn">',
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 5
            [
                true,
                true,
                true,
                [
                    '<link rel="stylesheet" href="http://localhost:8080/build-dev/styles.css"><link rel="stylesheet" href="http://localhost:8080/build-dev/styles2.css">',
                    '<script src="http://localhost:8080/build-dev/app1.js"></script><script src="http://localhost:8080/build-dev/app2.js"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app-dev::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app-dev::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 6
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::other_entry::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 7
            [
                true,
                true,
                true,
                [
                    '<link rel="stylesheet" href="/build/styles.css" integrity="sha384-4g+Zv0iELStVvA4/B27g4TQHUMwZttA5TEojjUyB8Gl5p7sarU4y+VTSGMrNab8n"><link rel="stylesheet" href="/build/styles2.css" integrity="sha384-hfZmq9+2oI5Cst4/F4YyS2tJAAYdGz7vqSMP8cJoa8bVOr2kxNRLxSw6P8UZjwUn">',
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>',
                    '<script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 8
            [
                true,
                true,
                true,
                [
                    '<link rel="stylesheet" href="/build/styles.css" integrity="sha384-4g+Zv0iELStVvA4/B27g4TQHUMwZttA5TEojjUyB8Gl5p7sarU4y+VTSGMrNab8n"><link rel="stylesheet" href="/build/styles2.css" integrity="sha384-hfZmq9+2oI5Cst4/F4YyS2tJAAYdGz7vqSMP8cJoa8bVOr2kxNRLxSw6P8UZjwUn">',
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>',
                    '<script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 9
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>',
                    '<link rel="stylesheet" href="/build/styles.css" integrity="sha384-4g+Zv0iELStVvA4/B27g4TQHUMwZttA5TEojjUyB8Gl5p7sarU4y+VTSGMrNab8n"><link rel="stylesheet" href="/build/styles2.css" integrity="sha384-hfZmq9+2oI5Cst4/F4YyS2tJAAYdGz7vqSMP8cJoa8bVOr2kxNRLxSw6P8UZjwUn">',
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ]
                ]
            ],
            // 10
            [
                true,
                true,
                true,
                [
                    '<link rel="stylesheet" href="/build/styles.css" integrity="sha384-4g+Zv0iELStVvA4/B27g4TQHUMwZttA5TEojjUyB8Gl5p7sarU4y+VTSGMrNab8n"><link rel="stylesheet" href="/build/styles2.css" integrity="sha384-hfZmq9+2oI5Cst4/F4YyS2tJAAYdGz7vqSMP8cJoa8bVOr2kxNRLxSw6P8UZjwUn">'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataProviderIncludeHeadSection
     *
     * @covers       \BlackForest\Contao\Encore\EventListener\Frontend\IncludeHeadSectionListener
     */
    public function testIncludeHeadSection(
        bool $hasPageLayut,
        bool $useEncore = false,
        bool $hasEncoreConfig = false,
        array $expected = [],
        array $encoreConfig = []
    ): void {
        unset($GLOBALS['TL_HEAD']);

        $this->createActivePage($hasPageLayut, $useEncore, $encoreConfig);

        $cache          = new ArrayAdapter();
        $includeSection = new IncludeHeadSectionListener(
            [
                '_default'   => \dirname(__DIR__, 2) . '/Fixtures/build/entrypoints.json',
                '_not_exist' => \dirname(__DIR__, 2) . '/Fixtures/build/_not_exist.json'
            ],
            $cache,
            $this->createTwigExtension($cache)
        );

        $includeSection->includeToSection('');

        if (!$hasPageLayut || !$useEncore || !$hasEncoreConfig) {
            $this->assertNull($cache->getItem('_default')->get());

            return;
        }

        if (!\count($expected)) {
            $this->assertArrayNotHasKey('TL_HEAD', $GLOBALS);

            return;
        }

        $this->assertSame($expected, $GLOBALS['TL_HEAD']);
        $this->assertSame('TL_HEAD', EncoreConstants::SECTION_HEAD);

        unset($GLOBALS['TL_HEAD']);
    }

    public function dataProviderIncludeJavascriptCombineSection(): array
    {
        return [
            // 0
            [false],
            // 1
            [true],
            // 2
            [true, true],
            // 3 Test with wrong context
            [
                true,
                true,
                true,
                [],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => 'app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => 'app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_not_exist::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_not_exist::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 4
            [
                true,
                true,
                true,
                [
                    'build/app1.js|static',
                    'build/app2.js|static'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 5
            [
                true,
                true,
                true,
                [
                    'http://localhost:8080/build-dev/app1.js',
                    'http://localhost:8080/build-dev/app2.js'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::app-dev::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::app-dev::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 6
            [
                true,
                true,
                true,
                [
                    'build/app1.js|static',
                    'build/app3.js|static'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::other_entry::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 7
            [
                true,
                true,
                true,
                [
                    'build/app1.js|static',
                    'build/app2.js|static',
                    'build/app3.js|static'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 8
            [
                true,
                true,
                true,
                [
                    'build/app1.js|static',
                    'build/app2.js|static',
                    'build/app3.js|static'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 9
            [
                true,
                true,
                true,
                [
                    'build/app3.js|static',
                    'build/app1.js|static',
                    'build/app2.js|static'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ]
                ]
            ],
            // 10
            [
                true,
                true,
                true,
                [
                    'build/app1.js|static',
                    'build/app3.js|static'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataProviderIncludeJavascriptCombineSection
     *
     * @covers       \BlackForest\Contao\Encore\EventListener\Frontend\IncludeJavascriptCombineSectionListener
     */
    public function testIncludeJavascriptCombineSection(
        bool $hasPageLayut,
        bool $useEncore = false,
        bool $hasEncoreConfig = false,
        array $expected = [],
        array $encoreConfig = []
    ): void {
        unset($GLOBALS['TL_JAVASCRIPT']);

        $this->createActivePage($hasPageLayut, $useEncore, $encoreConfig);

        $cache          = new ArrayAdapter();
        $includeSection = new IncludeJavascriptCombineSectionListener(
            [
                '_default'   => \dirname(__DIR__, 2) . '/Fixtures/build/entrypoints.json',
                '_not_exist' => \dirname(__DIR__, 2) . '/Fixtures/build/_not_exist.json'
            ],
            $cache,
            $this->createTwigExtension($cache)
        );

        $includeSection->includeToSection('');

        if (!$hasPageLayut || !$useEncore || !$hasEncoreConfig) {
            $this->assertNull($cache->getItem('_default')->get());

            return;
        }

        if (!\count($expected)) {
            $this->assertArrayNotHasKey('TL_JAVASCRIPT', $GLOBALS);

            return;
        }

        $this->assertSame($expected, $GLOBALS['TL_JAVASCRIPT']);
        $this->assertSame('TL_JAVASCRIPT', EncoreConstants::SECTION_JAVASCRIPT);

        unset($GLOBALS['TL_JAVASCRIPT']);
    }

    public function dataProviderIncludeJQueryCombineSection()
    {
        return [
            // 0
            [false],
            // 1
            [true],
            // 2
            [true, true],
            // 3 Test with wrong context
            [
                true,
                true,
                true,
                [],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => 'app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => 'app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_not_exist::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_not_exist::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_no_build_key::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_no_build_key::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 4
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 5
            [
                true,
                true,
                true,
                [
                    '<script src="http://localhost:8080/build-dev/app1.js"></script><script src="http://localhost:8080/build-dev/app2.js"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::app-dev::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::app-dev::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 6
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::other_entry::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 7
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>',
                    '<script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 8
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>',
                    '<script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 9
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>',
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ]
                ]
            ],
            // 10
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataProviderIncludeJQueryCombineSection
     *
     * @covers       \BlackForest\Contao\Encore\EventListener\Frontend\IncludeJQuerySectionListener
     */
    public function testIncludeJQueryCombineSection(
        bool $hasPageLayut,
        bool $useEncore = false,
        bool $hasEncoreConfig = false,
        array $expected = [],
        array $encoreConfig = []
    ): void {
        unset($GLOBALS['TL_JQUERY']);

        $this->createActivePage($hasPageLayut, $useEncore, $encoreConfig);

        $cache          = new ArrayAdapter();
        $includeSection = new IncludeJQuerySectionListener(
            [
                '_default'   => \dirname(__DIR__, 2) . '/Fixtures/build/entrypoints.json',
                '_not_exist' => \dirname(__DIR__, 2) . '/Fixtures/build/_not_exist.json'
            ],
            $cache,
            $this->createTwigExtension($cache)
        );

        $includeSection->includeToSection('');

        if (!$hasPageLayut || !$useEncore || !$hasEncoreConfig) {
            $this->assertNull($cache->getItem('_default')->get());

            return;
        }

        if (!\count($expected)) {
            $this->assertArrayNotHasKey('TL_JQUERY', $GLOBALS);

            return;
        }

        $this->assertSame($expected, $GLOBALS['TL_JQUERY']);
        $this->assertSame('TL_JQUERY', EncoreConstants::SECTION_JQUERY);

        unset($GLOBALS['TL_JQUERY']);
    }

    public function dataProviderIncludeMooToolsCombineSection(): array
    {
        return [
            // 0
            [false],
            // 1
            [true],
            // 2
            [true, true],
            // 3 Test with wrong context
            [
                true,
                true,
                true,
                [],
                [
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => 'app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => 'app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_not_exist::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_not_exist::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_no_build_key::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_no_build_key::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 4
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 5
            [
                true,
                true,
                true,
                [
                    '<script src="http://localhost:8080/build-dev/app1.js"></script><script src="http://localhost:8080/build-dev/app2.js"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::app-dev::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::app-dev::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 6
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::other_entry::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 7
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>',
                    '<script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 8
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>',
                    '<script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ],
            // 9
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>',
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ]
                ]
            ],
            // 10
            [
                true,
                true,
                true,
                [
                    '<script src="/build/app1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script><script src="/build/app3.js" integrity="sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9"></script>'
                ],
                [
                    [
                        'section'    => EncoreConstants::SECTION_BODY,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_HEAD,
                        'context'    => '_default::app::js',
                        'insertMode' => EncoreConstants::APPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JAVASCRIPT,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_JQUERY,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_MOOTOOLS,
                        'context'    => '_default::other_entry::js',
                        'insertMode' => EncoreConstants::PREPEND
                    ],
                    [
                        'section'    => EncoreConstants::SECTION_USERCSS,
                        'context'    => '_default::app::css',
                        'insertMode' => EncoreConstants::APPEND
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataProviderIncludeMooToolsCombineSection
     *
     * @covers       \BlackForest\Contao\Encore\EventListener\Frontend\IncludeMooToolsSectionListener
     */
    public function testIncludeMooToolsCombineSection(
        bool $hasPageLayut,
        bool $useEncore = false,
        bool $hasEncoreConfig = false,
        array $expected = [],
        array $encoreConfig = []
    ): void {
        unset($GLOBALS['TL_MOOTOOLS']);

        $this->createActivePage($hasPageLayut, $useEncore, $encoreConfig);

        $cache          = new ArrayAdapter();
        $includeSection = new IncludeMooToolsSectionListener(
            [
                '_default'   => \dirname(__DIR__, 2) . '/Fixtures/build/entrypoints.json',
                '_not_exist' => \dirname(__DIR__, 2) . '/Fixtures/build/_not_exist.json'
            ],
            $cache,
            $this->createTwigExtension($cache)
        );

        $includeSection->includeToSection('');

        if (!$hasPageLayut || !$useEncore || !$hasEncoreConfig) {
            $this->assertNull($cache->getItem('_default')->get());

            return;
        }

        if (!\count($expected)) {
            $this->assertArrayNotHasKey('TL_MOOTOOLS', $GLOBALS);

            return;
        }

        $this->assertSame($expected, $GLOBALS['TL_MOOTOOLS']);
        $this->assertSame('TL_MOOTOOLS', EncoreConstants::SECTION_MOOTOOLS);

        unset($GLOBALS['TL_MOOTOOLS']);
    }

    /**
     * @param EntrypointLookupInterface $lookup
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|EntrypointLookupCollection
     */
    private function mockEntryPointCollection(EntrypointLookupInterface $lookup): EntrypointLookupCollection
    {
        $collection = $this->createMock(EntrypointLookupCollection::class);
        $collection
            ->expects($this->any())
            ->method('getEntrypointLookup')
            ->willReturnCallback(
                function ($key) use ($lookup) {
                    return ('_default' === $key) ? $lookup : null;
                }
            );

        return $collection;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EntrypointLookup
     */
    private function createEntryPointLookup(CacheItemPoolInterface $cache): EntrypointLookup
    {
        return new EntrypointLookup(
            \dirname(__DIR__, 2) . '/Fixtures/build/entrypoints.json',
            $cache,
            '_default'
        );
    }

    /**
     * @return EntryFilesTwigExtension
     */
    private function createTwigExtension(CacheItemPoolInterface $cache): EntryFilesTwigExtension
    {
        $entryPointCollection = $this->mockEntryPointCollection($this->createEntryPointLookup($cache));

        $packages = $this->createMock(Packages::class);
        $packages
            ->expects($this->any())
            ->method('getUrl')
            ->willReturnCallback(
                function ($path) {
                    return (\in_array(
                        $path,
                        [
                            '/build/app1.js',
                            '/build/app2.js',
                            '/build/styles.css',
                            '/build/styles2.css',
                            'http://localhost:8080/build-dev/app1.js',
                            'http://localhost:8080/build-dev/app2.js',
                            'http://localhost:8080/build-dev/styles.css',
                            'http://localhost:8080/build-dev/styles2.css',
                            '/build/app1.js',
                            '/build/app3.js'
                        ]) ? $path : null
                    );
                }
            );

        $tagRenderer = new TagRenderer($entryPointCollection, $packages, []);

        $services = [
            'webpack_encore.entrypoint_lookup_collection' => $entryPointCollection,
            'webpack_encore.tag_renderer'                 => $tagRenderer
        ];

        $serviceContainer = $this->createMock(ContainerInterface::class);
        $serviceContainer
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function (string $id) use ($services) {
                    return $services[$id] ?? null;
                }
            );

        return new EntryFilesTwigExtension($serviceContainer);
    }

    private function createActivePage(bool $hasPageLayout, bool $useEncore, array $encoreConfig): void
    {
        $pageModel = $this->createMock(Model::class);

        $GLOBALS['objPage'] = $pageModel;

        if (!$hasPageLayout) {
            return;
        }

        $pageRelated = [];

        $pageLayout = $this->createMock(Model::class);

        $pageRelated['layout'] = $pageLayout;

        $pageModel
            ->expects($this->any())
            ->method('getRelated')
            ->willReturnCallback(
                function (string $key) use ($pageRelated) {
                    return $pageRelated[$key] ?? null;
                }
            );

        if (!$useEncore) {
            return;
        }

        $encoreConfigs = [
            $encoreConfig,
            \serialize($encoreConfig)
        ];
        \shuffle($encoreConfigs);
        $pageLayoutData = [
            'useEncore'    => '1',
            'encoreConfig' => $encoreConfigs[0]
        ];

        $pageLayout
            ->expects($this->any())
            ->method('__get')
            ->willReturnCallback(
                function ($key) use ($pageLayoutData) {
                    return $pageLayoutData[$key] ?? null;
                }
            );
    }
}
