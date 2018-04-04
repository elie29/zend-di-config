<?php

declare(strict_types=1);

namespace ZendTest\DI\Config;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Zend\DI\Config\Config;
use Zend\DI\Config\ContainerFactory;
use ZendTest\DI\Config\TestAsset\DelegatorService;
use ZendTest\DI\Config\TestAsset\DelegatorServiceFactory;
use ZendTest\DI\Config\TestAsset\DelegatorServiceFactory1;
use ZendTest\DI\Config\TestAsset\DelegatorServiceFactory2;
use ZendTest\DI\Config\TestAsset\Service;
use ZendTest\DI\Config\TestAsset\ServiceFactory;
use ZendTest\DI\Config\TestAsset\ServiceInterface;

class ConfigTest extends TestCase
{

    public function testConfigurationKeysValues()
    {
        $config = ['a' => new \DateTime(), 'b' => [1, 2, 3], 'c' => 'd'];

        $container = $this->getContainer($config);

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(\DateTime::class, $container->get('a'));
        self::assertSame([1, 2, 3], $container->get('b'));
        self::assertSame('d', $container->get('c'));
    }

    public function testConfigurationEnableCompilation()
    {
        $config = [Config::DI_CACHE_PATH => __DIR__, Config::ENABLE_CACHE_DEFINITION => false];

        $container = $this->getContainer($config);

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertSame(__DIR__, $container->get(Config::DI_CACHE_PATH));
        self::assertFalse($container->get(Config::ENABLE_CACHE_DEFINITION));

        @unlink(__DIR__ . '/CompiledContainer.php');
    }

    public function testConfigurationServices()
    {
        $config = ['dependencies' => [
            'services' => [
                Service::class => Service::class, // service -> service same key name
                'service-1' => Service::class,    // service -> service name
                'service-2' => new Service(),     // service -> object
                // service -> callable
                'service-3' => function () {
                    return new Service();
                },
                'service-4' => function (ContainerInterface $container) {
                    return $container->get('service-3');
                },
                'service-5' => function (Service $service) {
                    return $service;
                }
            ]
        ]];

        $container = $this->getContainer($config);

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(Service::class, $container->get(Service::class));
        self::assertInstanceOf(Service::class, $container->get('service-1'));
        self::assertInstanceOf(Service::class, $container->get('service-2'));
        self::assertInstanceOf(Service::class, $container->get('service-3'));
        self::assertInstanceOf(Service::class, $container->get('service-4'));
        self::assertInstanceOf(Service::class, $container->get('service-5'));
    }

    public function testConfigurationInvokables()
    {
        $config = ['dependencies' => [
            'invokables' => [
                Service::class => Service::class,
                'service-1' => Service::class,
            ]
        ]];

        $container = $this->getContainer($config);

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(Service::class, $container->get(Service::class));
        self::assertInstanceOf(Service::class, $container->get('service-1'));
    }

    public function testConfigurationFactories()
    {
        $config = ['dependencies' => [
            'services' => [
                'service-1' => ServiceFactory::class,
                'service-2' => new ServiceFactory()
            ],
            'factories' => [
                ServiceInterface::class => ServiceFactory::class,
                'factory-1' => ServiceFactory::class,
                'factory-2' => 'service-1', // factory -> factory instance
                'factory-3' => 'service-2', // factory -> factory instance
            ]
        ]];

        $container = $this->getContainer($config);

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(ServiceFactory::class, $container->get('service-1'));
        self::assertInstanceOf(ServiceFactory::class, $container->get('service-2'));
        self::assertInstanceOf(Service::class, $container->get(ServiceInterface::class));
        self::assertInstanceOf(Service::class, $container->get('factory-1'));
        self::assertInstanceOf(Service::class, $container->get('factory-2'));
        self::assertInstanceOf(Service::class, $container->get('factory-3'));
    }

    public function testConfigurationAliases()
    {
        $config = ['dependencies' => [
            'services' => [
                'service-1' => Service::class,
            ],
            'factories' => [
                'factory-1' => ServiceFactory::class
            ],
            'invokables' => [
                'invokable-1' => Service::class,
            ],
            'aliases' => [
                'alias-1' => 'service-1',   // alias -> service
                'alias-2' => 'factory-1',   // alias -> factory
                'alias-3' => 'invokable-1', // alias -> invokable
                'alias-4' => 'alias-1'      // alias -> alias
            ]
        ]];

        $container = $this->getContainer($config);

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(Service::class, $container->get('alias-1'));
        self::assertInstanceOf(Service::class, $container->get('alias-2'));
        self::assertInstanceOf(Service::class, $container->get('alias-3'));
        self::assertInstanceOf(Service::class, $container->get('alias-4'));
    }

    public function testConfigurationDelegators()
    {
        $config = ['dependencies' => [
            'services' => [
                'service-1' => Service::class,
            ],
            'delegators' => [
                'service-1' => [
                    DelegatorServiceFactory::class
                ]
            ]
        ]];

        $container = $this->getContainer($config);

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(DelegatorService::class, $container->get('service-1'));
        self::assertInstanceOf(ServiceInterface::class, $container->get('service-1')->service);
    }

    public function testConfigurationMultiDelegators()
    {
        $config = ['dependencies' => [
            'factories' => [
                'key-1' => ServiceFactory::class,
            ],
            'delegators' => [
                'key-1' => [
                    DelegatorServiceFactory1::class,
                    DelegatorServiceFactory2::class
                ]
            ]
        ]];

        $container = $this->getContainer($config);

        $expected = [DelegatorServiceFactory1::class, DelegatorServiceFactory2::class];

        self::assertNotEmpty($container->getKnownEntryNames());
        self::assertInstanceOf(ServiceInterface::class, $container->get('key-1'));
        self::assertEquals($expected, $container->get('key-1')->getInjected());
    }

    private function getContainer(array $config): ContainerInterface
    {
        $factory = new ContainerFactory();
        $config = new Config($config);

        return $factory($config);
    }
}
