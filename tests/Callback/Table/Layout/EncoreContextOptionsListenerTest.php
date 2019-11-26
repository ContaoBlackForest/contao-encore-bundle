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

namespace BlackForest\Contao\Encore\Test\Callback\Table\Layout;

use BlackForest\Contao\Encore\Callback\Table\Layout\EncoreContextOptionsListener;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use MenAtWork\MultiColumnWizardBundle\Contao\Widgets\MultiColumnWizard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \BlackForest\Contao\Encore\Callback\Table\Layout\EncoreContextOptionsListener
 */
class EncoreContextOptionsListenerTest extends TestCase
{
    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // Some class mapping for Contao 4.4.
        self::aliasContaoClass('System');
        self::aliasContaoClass('Config');
        self::aliasContaoClass('Controller');
        self::aliasContaoClass('Database');
        self::aliasContaoClass('TemplateInheritance');

        \defined('TL_MODE') ? null : \define('TL_MODE', 'BE');
        \defined('TL_ROOT') ? null : \define('TL_ROOT', '');
    }

    public function dataproviderEncoreContextOptions(): array
    {
        return [
            'is not a mcw widget'                                =>
                [false, false],
            'is mcw widget and has not the column field context' =>
                [true, false],
            'is mcw widget and has the column field context'     =>
                [true, true],
        ];
    }

    /**
     * @dataProvider dataproviderEncoreContextOptions
     */
    public function testEncoreContextOptions(bool $hasColumnFields, bool $hasContext): void
    {
        $this->setupContainerWithConfiguration();

        $builds = [
            '_default'  => \dirname(__DIR__, 3) . '/Fixtures/build/entrypoints.json',
            '_notExist' => 'not exist entrypoints in this build'
        ];

        $cache = new ArrayAdapter();

        $widget = new MultiColumnWizard();
        if ($hasColumnFields) {
            $widget->columnFields = [];
        }
        if ($hasContext) {
            $widget->columnFields = ['context' => ''];
        }

        $listener = new EncoreContextOptionsListener($builds, $cache);
        $options  = $listener->__invoke($widget);

        if (!$hasContext) {
            self::assertNull($cache->getItem('_default')->get());
            self::assertSame([], $options);

            return;
        }

        $expectedCache = [
            'entrypoints' => [
                'app'         => [
                    'js'  => [
                        '/build/app1.js',
                        '/build/app2.js'
                    ],
                    'css' => [
                        '/build/styles.css',
                        '/build/styles2.css'
                    ]
                ],
                'app-dev'     => [
                    'js'  => [
                        'http://localhost:8080/build-dev/app1.js',
                        'http://localhost:8080/build-dev/app2.js'
                    ],
                    'css' => [
                        'http://localhost:8080/build-dev/styles.css',
                        'http://localhost:8080/build-dev/styles2.css',
                    ]
                ],
                'other_entry' => [
                    'js' => [
                        '/build/app1.js',
                        '/build/app3.js'
                    ]
                ]
            ],
            'integrity'   => [
                '/build/app1.js'     => 'sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc',
                '/build/app2.js'     => 'sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J',
                '/build/styles.css'  => 'sha384-4g+Zv0iELStVvA4/B27g4TQHUMwZttA5TEojjUyB8Gl5p7sarU4y+VTSGMrNab8n',
                '/build/styles2.css' => 'sha384-hfZmq9+2oI5Cst4/F4YyS2tJAAYdGz7vqSMP8cJoa8bVOr2kxNRLxSw6P8UZjwUn',
                '/build/app3.js'     => 'sha384-ZU3hiTN/+Va9WVImPi+cI0/j/Q7SzAVezqL1aEXha8sVgE5HU6/0wKUxj1LEnkC9',
            ],
        ];

        $expectedOptions = [
            '_default::app::css'        => 'app::css',
            '_default::app::js'         => 'app::js',
            '_default::app-dev::css'    => 'app-dev::css',
            '_default::app-dev::js'     => 'app-dev::js',
            '_default::other_entry::js' => 'other_entry::js',
        ];

        self::assertSame($expectedCache, $cache->getItem('_default')->get());
        self::assertSame($expectedOptions, $options);
    }

    private function setupContainerWithConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.cache_dir', '/var/cache');
        $container->setParameter('kernel.project_dir', '');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $container->set('event_dispatcher', $dispatcher);

        $connection = new Connection([], $this->createMock(Driver::class));
        $container->set('database_connection', $connection);

        System::setContainer($container);
    }

    /**
     * Mapping between root namespace of contao and the contao namespace.
     * Can map class, interface and trait.
     *
     * @param string $class The name of the class
     *
     * @return void
     */
    private static function aliasContaoClass($class): void
    {
        // Class.
        if (!\class_exists($class, true) && \class_exists('\\Contao\\' . $class, true)) {
            if (!\class_exists($class, false)) {
                \class_alias('\\Contao\\' . $class, $class);
            }
            return;
        }
        // Trait.
        if (!\trait_exists($class, true) && \trait_exists('\\Contao\\' . $class, true)) {
            if (!\trait_exists($class, false)) {
                \class_alias('\\Contao\\' . $class, $class);
            }
            return;
        }
        // Interface.
        if (!\interface_exists($class, true) && \interface_exists('\\Contao\\' . $class, true)) {
            if (!\interface_exists($class, false)) {
                \class_alias('\\Contao\\' . $class, $class);
            }
            return;
        }
    }
}
