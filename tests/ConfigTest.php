<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config;

use DateTime;
use DI\ContainerBuilder;
use DI\Definition\Exception\InvalidDefinition;
use Elie\PHPDI\Config\Config;
use Elie\PHPDI\Config\ConfigInterface;
use Elie\PHPDI\Config\ContainerFactory;
use ElieTest\PHPDI\Config\TestAsset\DelegatorService;
use ElieTest\PHPDI\Config\TestAsset\DelegatorServiceFactory;
use ElieTest\PHPDI\Config\TestAsset\DelegatorServiceFactory1;
use ElieTest\PHPDI\Config\TestAsset\DelegatorServiceFactory2;
use ElieTest\PHPDI\Config\TestAsset\InvalidDelegatorFactory;
use ElieTest\PHPDI\Config\TestAsset\InvalidServiceFactory;
use ElieTest\PHPDI\Config\TestAsset\Service;
use ElieTest\PHPDI\Config\TestAsset\ServiceFactory;
use ElieTest\PHPDI\Config\TestAsset\ServiceInterface;
use ElieTest\PHPDI\Config\TestAsset\UserManager;
use Error;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use TypeError;

use function sys_get_temp_dir;

class ConfigTest extends TestCase
{
    public function testConfigurationEnableCache(): void
    {
        $builder = $this->createMock(ContainerBuilder::class);

        $builder->expects($this->once())->method('enableDefinitionCache');

        $config = new Config([ConfigInterface::ENABLE_CACHE_DEFINITION => true]);
        $config->configureContainer($builder);
    }

    public function testConfigurationDisableAutowire(): void
    {
        $builder = $this->createMock(ContainerBuilder::class);

        $builder->expects($this->once())->method('useAutowiring');

        $config = new Config([ConfigInterface::USE_AUTOWIRE => false]);
        $config->configureContainer($builder);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testConfigurationKeysValues(): void
    {
        $config = ['a' => new DateTime(), 'b' => [1, 2, 3], 'c' => 'd'];

        $container = $this->getContainer($config);

        $config = $container->get(ConfigInterface::CONFIG);

        self::assertNotEmpty($config);
        self::assertInstanceOf(DateTime::class, $config['a']);
        self::assertSame([1, 2, 3], $config['b']);
        self::assertSame('d', $config['c']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testConfigurationEnableCompilation(): void
    {
        $url       = sys_get_temp_dir();
        $config    = [ConfigInterface::DI_CACHE_PATH => $url, ConfigInterface::ENABLE_CACHE_DEFINITION => false];
        $container = $this->getContainer($config);

        $config = $container->get(ConfigInterface::CONFIG);

        self::assertNotEmpty($config);
        self::assertSame($url, $config[ConfigInterface::DI_CACHE_PATH]);
        self::assertFalse($config[ConfigInterface::ENABLE_CACHE_DEFINITION]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testConfigurationWriteProxiesToFile(): void
    {
        $url       = sys_get_temp_dir();
        $config    = [ConfigInterface::DI_PROXY_PATH => $url];
        $container = $this->getContainer($config);

        $config = $container->get(ConfigInterface::CONFIG);

        self::assertNotEmpty($config);
        self::assertSame($url, $config[ConfigInterface::DI_PROXY_PATH]);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
    public function testConfigurationServices(): void
    {
        $config = [
            'dependencies' => [
                'services' => [
                    Service::class => Service::class, // service -> service same key name
                    'service-1'    => Service::class, // service -> service name
                    'service-2'    => new Service(), // service -> object
                    // service -> callable
                    'service-3' => function (): Service {
                        return new Service();
                    },
                    'service-4' => function (ContainerInterface $container) {
                        return $container->get('service-3');
                    },
                    'service-5' => function (Service $service): Service {
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
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

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
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

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
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

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testFactoryThrowsException(): void
    {
        $config = [
            'dependencies' => [
                'factories' => [
                    'invalid-service' => InvalidServiceFactory::class,
                ],
            ],
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Factory error: This factory always fails.');

        $container = $this->getContainer($config);
        $container->get('invalid-service');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testDelegatorThrowsException(): void
    {
        $config = [
            'dependencies' => [
                'services'   => [
                    'service-1' => Service::class,
                ],
                'delegators' => [
                    'service-1' => [
                        InvalidDelegatorFactory::class,
                    ],
                ],
            ],
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Delegator error: This delegator always fails.');

        $container = $this->getContainer($config);
        $container->get('service-1');
    }

    /**
     * @throws Exception
     */
    private function getContainer(array $config): ContainerInterface
    {
        $factory = new ContainerFactory();
        $config  = new Config($config);

        return $factory($config);
    }

    /**
     * Negative test: dependencies is not an array
     *
     * @throws Exception
     */
    public function testConfigWithNonArrayDependencies(): void
    {
        $this->expectException(TypeError::class);
        $factory = new ContainerFactory();
        $config  = new Config(['dependencies' => 'not-an-array']);
        $factory($config);
    }

    /**
     * Negative test: service factory throws exception
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testServiceFactoryThrowsException(): void
    {
        $factory   = new ContainerFactory();
        $config    = new Config([
            'dependencies' => [
                'factories' => [
                    'bad-service' => function () {
                        throw new RuntimeException('Factory error');
                    },
                ],
            ],
        ]);
        $container = $factory($config);
        $this->expectException(RuntimeException::class);
        $container->get('bad-service');
    }

    /**
     * Negative test: delegator is not callable
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testDelegatorNotCallable(): void
    {
        $factory   = new ContainerFactory();
        $config    = new Config([
            'dependencies' => [
                'services'   => [
                    'service-1' => Service::class,
                ],
                'delegators' => [
                    'service-1' => [
                        'NotARealClass',
                    ],
                ],
            ],
        ]);
        $container = $factory($config);
        $this->expectException(Error::class);
        $container->get('service-1');
    }

    /**
     * Negative test: autowires contains invalid value
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testAutowiresWithInvalidValue(): void
    {
        $factory   = new ContainerFactory();
        $config    = new Config([
            'dependencies' => [
                'autowires' => ['123'],
            ],
        ]);
        $container = $factory($config);
        $this->expectException(InvalidDefinition::class);
        $container->get('123');
    }
}
