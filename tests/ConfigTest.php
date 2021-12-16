<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config;

use DateTime;
use DI\ContainerBuilder;
use Elie\PHPDI\Config\Config;
use Elie\PHPDI\Config\ContainerFactory;
use ElieTest\PHPDI\Config\TestAsset\DelegatorService;
use ElieTest\PHPDI\Config\TestAsset\DelegatorServiceFactory;
use ElieTest\PHPDI\Config\TestAsset\DelegatorServiceFactory1;
use ElieTest\PHPDI\Config\TestAsset\DelegatorServiceFactory2;
use ElieTest\PHPDI\Config\TestAsset\Service;
use ElieTest\PHPDI\Config\TestAsset\ServiceFactory;
use ElieTest\PHPDI\Config\TestAsset\ServiceInterface;
use ElieTest\PHPDI\Config\TestAsset\UserManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function sys_get_temp_dir;

class ConfigTest extends TestCase
{
    public function testConfigurationEnableCache(): void
    {
        $builder = $this->createMock(ContainerBuilder::class);

        $builder->expects($this->once())->method('enableDefinitionCache');

        $config = new Config([Config::ENABLE_CACHE_DEFINITION => true]);
        $config->configureContainer($builder);
    }

    public function testConfigurationDisableAutowire(): void
    {
        $builder = $this->createMock(ContainerBuilder::class);

        $builder->expects($this->once())->method('useAutowiring');

        $config = new Config([Config::USE_AUTOWIRE => false]);
        $config->configureContainer($builder);
    }

    public function testConfigurationKeysValues(): void
    {
        $config = ['a' => new DateTime(), 'b' => [1, 2, 3], 'c' => 'd'];

        $container = $this->getContainer($config);

        $config = $container->get(Config::CONFIG);

        self::assertNotEmpty($config);
        self::assertInstanceOf(DateTime::class, $config['a']);
        self::assertSame([1, 2, 3], $config['b']);
        self::assertSame('d', $config['c']);
    }

    public function testConfigurationEnableCompilation(): void
    {
        $url       = sys_get_temp_dir();
        $config    = [Config::DI_CACHE_PATH => $url, Config::ENABLE_CACHE_DEFINITION => false];
        $container = $this->getContainer($config);

        $config = $container->get(Config::CONFIG);

        self::assertNotEmpty($config);
        self::assertSame($url, $config[Config::DI_CACHE_PATH]);
        self::assertFalse($config[Config::ENABLE_CACHE_DEFINITION]);
    }

    public function testConfigurationWriteProxiesToFile(): void
    {
        $url       = sys_get_temp_dir();
        $config    = [Config::DI_PROXY_PATH => $url];
        $container = $this->getContainer($config);

        $config = $container->get(Config::CONFIG);

        self::assertNotEmpty($config);
        self::assertSame($url, $config[Config::DI_PROXY_PATH]);
    }

    public function testConfigurationServices(): void
    {
        $config = [
            'dependencies' => [
                'services' => [
                    Service::class => Service::class, // service -> service same key name
                    'service-1'    => Service::class, // service -> service name
                    'service-2'    => new Service(), // service -> object
                    // service -> callable
                    'service-3' => function () {
                        return new Service();
                    },
                    'service-4' => function (ContainerInterface $container) {
                        return $container->get('service-3');
                    },
                    'service-5' => function (Service $service) {
                        return $service;
                    },
                ],
            ],
        ];

        $container = $this->getContainer($config);

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(Service::class, $container->get(Service::class));
        self::assertInstanceOf(Service::class, $container->get('service-1'));
        self::assertInstanceOf(Service::class, $container->get('service-2'));
        self::assertInstanceOf(Service::class, $container->get('service-3'));
        self::assertInstanceOf(Service::class, $container->get('service-4'));
        self::assertInstanceOf(Service::class, $container->get('service-5'));
    }

    public function testConfigurationInvokables(): void
    {
        $config = [
            'dependencies' => [
                'invokables' => [
                    Service::class => Service::class,
                    'service-1'    => Service::class,
                    Service::class,
                ],
            ],
        ];

        $container = $this->getContainer($config);

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(Service::class, $container->get(Service::class));
        self::assertInstanceOf(Service::class, $container->get('service-1'));
    }

    public function testConfigurationAutowires(): void
    {
        $config = [
            'dependencies' => [
                'autowires' => [
                    UserManager::class, // array of service
                ],
                'aliases'   => [
                    'user-manager1' => UserManager::class,
                    'user-manager2' => UserManager::class,
                ],
            ],
        ];

        $container = $this->getContainer($config);

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(UserManager::class, $container->get(UserManager::class));
        self::assertInstanceOf(UserManager::class, $container->get('user-manager1'));
        self::assertSame($container->get('user-manager1'), $container->get('user-manager2'));
    }

    public function testConfigurationFactories(): void
    {
        $config = [
            'dependencies' => [
                'services'  => [
                    'service-1' => ServiceFactory::class,
                    'service-2' => new ServiceFactory(),
                ],
                'factories' => [
                    ServiceInterface::class => ServiceFactory::class,
                    'factory-1'             => ServiceFactory::class,
                    'factory-2'             => 'service-1', // factory -> factory instance
                    'factory-3'             => 'service-2', // factory -> factory instance
                ],
            ],
        ];

        $container = $this->getContainer($config);

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(ServiceFactory::class, $container->get('service-1'));
        self::assertInstanceOf(ServiceFactory::class, $container->get('service-2'));
        self::assertInstanceOf(Service::class, $container->get(ServiceInterface::class));
        self::assertInstanceOf(Service::class, $container->get('factory-1'));
        self::assertInstanceOf(Service::class, $container->get('factory-2'));
        self::assertInstanceOf(Service::class, $container->get('factory-3'));
    }

    public function testConfigurationAliases(): void
    {
        $config = [
            'dependencies' => [
                'services'   => [
                    'service-1' => Service::class,
                ],
                'factories'  => [
                    'factory-1' => ServiceFactory::class,
                ],
                'invokables' => [
                    'invokable-1' => Service::class,
                ],
                'aliases'    => [
                    'alias-1' => 'service-1', // alias -> service
                    'alias-2' => 'factory-1', // alias -> factory
                    'alias-3' => 'invokable-1', // alias -> invokable
                    'alias-4' => 'alias-1', // alias -> alias
                ],
            ],
        ];

        $container = $this->getContainer($config);

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(Service::class, $container->get('alias-1'));
        self::assertInstanceOf(Service::class, $container->get('alias-2'));
        self::assertInstanceOf(Service::class, $container->get('alias-3'));
        self::assertInstanceOf(Service::class, $container->get('alias-4'));
    }

    public function testConfigurationDelegators(): void
    {
        $config = [
            'dependencies' => [
                'services'   => [
                    'service-1' => Service::class,
                ],
                'delegators' => [
                    'service-1' => [
                        DelegatorServiceFactory::class,
                    ],
                ],
            ],
        ];

        $container = $this->getContainer($config);

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(DelegatorService::class, $container->get('service-1'));
        self::assertInstanceOf(ServiceInterface::class, $container->get('service-1')->service);
    }

    public function testConfigurationMultiDelegators(): void
    {
        $config = [
            'dependencies' => [
                'factories'  => [
                    'key-1' => ServiceFactory::class,
                ],
                'delegators' => [
                    'key-1' => [
                        DelegatorServiceFactory1::class,
                        DelegatorServiceFactory2::class,
                    ],
                ],
            ],
        ];

        $container = $this->getContainer($config);

        $expected = [DelegatorServiceFactory1::class, DelegatorServiceFactory2::class];

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(ServiceInterface::class, $container->get('key-1'));
        self::assertEquals($expected, $container->get('key-1')->getInjected());
    }

    private function getContainer(array $config): ContainerInterface
    {
        $factory = new ContainerFactory();
        $config  = new Config($config);

        return $factory($config);
    }
}
