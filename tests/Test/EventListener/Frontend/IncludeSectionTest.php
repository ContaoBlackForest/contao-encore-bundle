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
    public function dataProviderInlcudeBodySection()
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
            // 6
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
            // 8
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
            // 9
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
     * @dataProvider dataProviderInlcudeBodySection
     *
     * @covers       \BlackForest\Contao\Encore\EventListener\Frontend\IncludeBodySectionListener
     */
    public function testInlcudeBodySection(
        bool $hasPageLayut,
        bool $useEncore = false,
        bool $hasEncoreConfig = false,
        array $expected = [],
        array $encoreConfig = []
    ) {
        unset($GLOBALS[EncoreConstants::SECTION_BODY]);

        $this->createActivePage($hasPageLayut, $useEncore, $encoreConfig);

        $cache          = $this->mockCacheItemPool();
        $includeSection = new IncludeBodySectionListener(
            ['_default' => __DIR__ . '/../../../fixtures/build/entrypoints.json'],
            $cache,
            $this->createTwigExtension($cache)
        );

        $includeSection->includeToSection('');

        if (!$hasPageLayut || !$useEncore || !$hasEncoreConfig) {
            $this->assertNull($cache->getItem('_default')->get());

            return;
        }

        if (!\count($expected)) {
            $this->assertArrayNotHasKey(EncoreConstants::SECTION_BODY, $GLOBALS);

            return;
        }

        $this->assertSame($expected, $GLOBALS[EncoreConstants::SECTION_BODY]);
    }

    public function dataProviderIncludeCSSCombineSection()
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
            // 6
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
    ) {
        unset($GLOBALS[EncoreConstants::SECTION_USERCSS]);

        $this->createActivePage($hasPageLayut, $useEncore, $encoreConfig);

        $cache          = $this->mockCacheItemPool();
        $includeSection = new IncludeCSSCombineSectionListener(
            ['_default' => __DIR__ . '/../../../fixtures/build/entrypoints.json'],
            $cache,
            $this->createTwigExtension($cache)
        );

        $includeSection->includeToSection('');

        if (!$hasPageLayut || !$useEncore || !$hasEncoreConfig) {
            $this->assertNull($cache->getItem('_default')->get());

            return;
        }

        if (!\count($expected)) {
            $this->assertArrayNotHasKey(EncoreConstants::SECTION_USERCSS, $GLOBALS);

            return;
        }

        $this->assertSame($expected, $GLOBALS[EncoreConstants::SECTION_USERCSS]);
    }

    public function dataProviderIncludeHeadSection()
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
            // 6
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
            // 8
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
            // 9
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
    ) {
        unset($GLOBALS[EncoreConstants::SECTION_HEAD]);

        $this->createActivePage($hasPageLayut, $useEncore, $encoreConfig);

        $cache          = $this->mockCacheItemPool();
        $includeSection = new IncludeHeadSectionListener(
            ['_default' => __DIR__ . '/../../../fixtures/build/entrypoints.json'],
            $cache,
            $this->createTwigExtension($cache)
        );

        $includeSection->includeToSection('');

        if (!$hasPageLayut || !$useEncore || !$hasEncoreConfig) {
            $this->assertNull($cache->getItem('_default')->get());

            return;
        }

        if (!\count($expected)) {
            $this->assertArrayNotHasKey(EncoreConstants::SECTION_HEAD, $GLOBALS);

            return;
        }

        $this->assertSame($expected, $GLOBALS[EncoreConstants::SECTION_HEAD]);
    }

    public function dataProviderIncludeJavascriptCombineSection()
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
            // 6
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
            // 8
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
            // 9
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
    ) {
        unset($GLOBALS[EncoreConstants::SECTION_JAVASCRIPT]);

        $this->createActivePage($hasPageLayut, $useEncore, $encoreConfig);

        $cache          = $this->mockCacheItemPool();
        $includeSection = new IncludeJavascriptCombineSectionListener(
            ['_default' => __DIR__ . '/../../../fixtures/build/entrypoints.json'],
            $cache,
            $this->createTwigExtension($cache)
        );

        $includeSection->includeToSection('');

        if (!$hasPageLayut || !$useEncore || !$hasEncoreConfig) {
            $this->assertNull($cache->getItem('_default')->get());

            return;
        }

        if (!\count($expected)) {
            $this->assertArrayNotHasKey(EncoreConstants::SECTION_JAVASCRIPT, $GLOBALS);

            return;
        }

        $this->assertSame($expected, $GLOBALS[EncoreConstants::SECTION_JAVASCRIPT]);
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
            // 6
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
            // 8
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
            // 9
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
    ) {
        unset($GLOBALS[EncoreConstants::SECTION_JQUERY]);

        $this->createActivePage($hasPageLayut, $useEncore, $encoreConfig);

        $cache          = $this->mockCacheItemPool();
        $includeSection = new IncludeJQuerySectionListener(
            ['_default' => __DIR__ . '/../../../fixtures/build/entrypoints.json'],
            $cache,
            $this->createTwigExtension($cache)
        );

        $includeSection->includeToSection('');

        if (!$hasPageLayut || !$useEncore || !$hasEncoreConfig) {
            $this->assertNull($cache->getItem('_default')->get());

            return;
        }

        if (!\count($expected)) {
            $this->assertArrayNotHasKey(EncoreConstants::SECTION_JQUERY, $GLOBALS);

            return;
        }

        $this->assertSame($expected, $GLOBALS[EncoreConstants::SECTION_JQUERY]);
    }

    public function dataProviderIncludeMooToolsCombineSection()
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
            // 6
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
            // 8
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
            // 9
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
    ) {
        unset($GLOBALS[EncoreConstants::SECTION_MOOTOOLS]);

        $this->createActivePage($hasPageLayut, $useEncore, $encoreConfig);

        $cache          = $this->mockCacheItemPool();
        $includeSection = new IncludeMooToolsSectionListener(
            ['_default' => __DIR__ . '/../../../fixtures/build/entrypoints.json'],
            $cache,
            $this->createTwigExtension($cache)
        );

        $includeSection->includeToSection('');

        if (!$hasPageLayut || !$useEncore || !$hasEncoreConfig) {
            $this->assertNull($cache->getItem('_default')->get());

            return;
        }

        if (!\count($expected)) {
            $this->assertArrayNotHasKey(EncoreConstants::SECTION_MOOTOOLS, $GLOBALS);

            return;
        }

        $this->assertSame($expected, $GLOBALS[EncoreConstants::SECTION_MOOTOOLS]);
    }

    /**
     * @param EntrypointLookupInterface $lookup
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|EntrypointLookupCollection
     */
    private function mockEntryPointCollection(EntrypointLookupInterface $lookup)
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
            __DIR__ . '/../../../fixtures/build/entrypoints.json',
            $cache,
            '_default'
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|CacheItemPoolInterface
     */
    private function mockCacheItemPool()
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);

        $cacheItems = [
            '_default' => $this->mockDefaultCacheItem()
        ];

        $cache
            ->expects($this->any())
            ->method('getItem')
            ->willReturnCallback(
                function ($key) use ($cacheItems) {
                    return $cacheItems[$key] ?? null;
                }
            );

        return $cache;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|CacheItemInterface
     */
    private function mockDefaultCacheItem()
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItemValue = null;

        $cacheItem
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function () use (&$cacheItemValue) {
                    return $cacheItemValue;
                }
            );

        $cacheItem
            ->expects($this->any())
            ->method('set')
            ->willReturnCallback(
                function ($value) use (&$cacheItemValue, $cacheItem) {
                    $cacheItemValue = $value;

                    return $cacheItem;
                }
            );

        return $cacheItem;
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

        $pageLayoutData = [
            'useEncore'    => '1',
            'encoreConfig' => \serialize($encoreConfig)
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
