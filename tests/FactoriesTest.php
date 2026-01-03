<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config;

use DI\Container;
use Elie\PHPDI\Config\Config;
use Elie\PHPDI\Config\ConfigInterface;
use Elie\PHPDI\Config\ContainerFactory;
use ElieTest\PHPDI\Config\TestAsset\ConfigFactory;
use ElieTest\PHPDI\Config\TestAsset\ConfigurableServiceFactory;
use ElieTest\PHPDI\Config\TestAsset\InvalidServiceFactory;
use ElieTest\PHPDI\Config\TestAsset\Mailer;
use ElieTest\PHPDI\Config\TestAsset\MailerFactory;
use ElieTest\PHPDI\Config\TestAsset\Service;
use ElieTest\PHPDI\Config\TestAsset\ServiceFactory;
use ElieTest\PHPDI\Config\TestAsset\ServiceInterface;
use ElieTest\PHPDI\Config\TestAsset\UserManager;
use ElieTest\PHPDI\Config\TestAsset\UserManagerFactory;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Comprehensive test suite for factories configuration.
 *
 * Factories are classes or callables that create service instances.
 * They provide full control over instantiation and can:
 * - Inject dependencies from the container
 * - Use configuration values
 * - Apply conditional logic
 * - Perform complex initialization
 * - Return any type of value (objects, arrays, scalars)
 *
 * Factory signature: __invoke(ContainerInterface $container): mixed
 */
class FactoriesTest extends TestCase
{
    /**
     * Test a basic factory class that creates a simple service.
     * Factories must implement __invoke(ContainerInterface $container).
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testBasicFactoryClass(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'factories' => [
                    ServiceInterface::class => ServiceFactory::class,
                ],
            ],
        ]);

        $instance = $container->get(ServiceInterface::class);

        $this->assertInstanceOf(ServiceInterface::class, $instance);
    }

    /**
     * Test factory that retrieves dependencies from the container.
     * Demonstrates how factories orchestrate dependency injection.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryWithContainerDependencies(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'factories' => [
                    Mailer::class => MailerFactory::class,
                    UserManager::class => UserManagerFactory::class,
                ],
            ],
        ]);

        $userManager = $container->get(UserManager::class);

        $this->assertInstanceOf(UserManager::class, $userManager);
        $userManager->register('test@example.com'); // Should not throw
    }

    /**
     * Test factory that uses configuration from the container.
     * Factories can access the 'config' service for customization.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryUsingConfiguration(): void
    {
        $container = $this->getContainer([
            'service_name' => 'CustomService',
            'dependencies' => [
                'factories' => [
                    'configured.service' => ConfigurableServiceFactory::class,
                ],
            ],
        ]);

        $service = $container->get('configured.service');

        $this->assertInstanceOf(Service::class, $service);
        $this->assertContains('CustomService', $service->getInjected());
    }

    /**
     * Test factory referenced by service name.
     * A factory instance can be registered as a service and referenced.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryReferencedAsService(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    'my.factory' => new ServiceFactory(),
                ],
                'factories' => [
                    'service.from.ref' => 'my.factory',
                ],
            ],
        ]);

        $instance = $container->get('service.from.ref'); // resolves to service instance
        $myFactory = $container->get('my.factory'); // resolves to factory instance

        $this->assertNotSame($myFactory($container), $myFactory($container));
        $this->assertNotSame($myFactory($container), $instance);
        $this->assertInstanceOf(Service::class, $instance);
    }

    /**
     * Test factory as a closure.
     * Factories can be inline closures for simple logic.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryAsClosure(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'factories' => [
                    'closure.service' => function (): Service {
                        $service = new Service();
                        $service->inject('from-closure');
                        return $service;
                    },
                ],
            ],
        ]);

        $instance = $container->get('closure.service');

        $this->assertInstanceOf(Service::class, $instance);
        $this->assertContains('from-closure', $instance->getInjected());
    }

    /**
     * Test factory returning non-object values.
     * Factories can return arrays, scalars, or any PHP value.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryReturningArray(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'factories' => [
                    'db.config' => ConfigFactory::class,
                ],
            ],
        ]);

        $config = $container->get('db.config');

        $this->assertIsArray($config);
        $this->assertSame('localhost', $config['db_host']);
        $this->assertSame(3306, $config['db_port']);
        $this->assertSame('mydb', $config['db_name']);
    }

    /**
     * Test factory returning scalar value via closure.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryReturningScalar(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'factories' => [
                    'app.version' => function (): string {
                        return '1.0.0';
                    },
                ],
            ],
        ]);

        $version = $container->get('app.version');

        $this->assertSame('1.0.0', $version);
    }

    /**
     * Test multiple factories in a single configuration.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testMultipleFactories(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'factories' => [
                    Mailer::class => MailerFactory::class,
                    ServiceInterface::class => ServiceFactory::class,
                    UserManager::class => UserManagerFactory::class,
                ],
            ],
        ]);

        $this->assertInstanceOf(Mailer::class, $container->get(Mailer::class));
        $this->assertInstanceOf(Service::class, $container->get(ServiceInterface::class));
        $this->assertInstanceOf(UserManager::class, $container->get(UserManager::class));
    }

    /**
     * Test that factory-created services are shared in PHP-DI.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryServicesAreSharedByDefault(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'factories' => [
                    Service::class => ServiceFactory::class,
                ],
            ],
        ]);

        $instance1 = $container->get(Service::class);
        $instance2 = $container->get(Service::class);

        $this->assertSame($instance1, $instance2);
        $this->assertSame($instance1->getUnique(), $instance2->getUnique());
    }

    /**
     * Test a factory with conditional logic based on the container state.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryWithConditionalLogic(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'factories' => [
                    'conditional.service' => function (ContainerInterface $container): Service {
                        $service = new Service();

                        $config = $container->get(ConfigInterface::CONFIG);
                        if (!empty($config['debug_mode'])) {
                            $service->inject('debug-enabled');
                        }

                        return $service;
                    },
                ],
            ],
        ]);

        $container->set(ConfigInterface::CONFIG, ['debug_mode' => true]);

        $service = $container->get('conditional.service');

        $this->assertContains('debug-enabled', $service->getInjected());
    }

    /**
     * Test factory that throws an exception.
     * Exceptions from factories are propagated to the caller.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryThrowsException(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'factories' => [
                    'invalid.service' => InvalidServiceFactory::class,
                ],
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Factory error: This factory always fails.');

        $container->get('invalid.service');
    }

    /**
     * Test factory accessing another factory-created service.
     * Demonstrates factory chaining and composition.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryAccessingAnotherFactoryService(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'factories' => [
                    'base.service' => function (): Service {
                        return new Service();
                    },
                    'wrapper.service' => function (ContainerInterface $container): Service {
                        $base = $container->get('base.service');
                        $base->inject('wrapped');
                        return $base;
                    },
                ],
            ],
        ]);

        $wrapper = $container->get('wrapper.service');

        $this->assertInstanceOf(Service::class, $wrapper);
        $this->assertContains('wrapped', $wrapper->getInjected());
    }

    /**
     * Test factory with interface mapping.
     * Common pattern for dependency inversion.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryWithInterfaceMapping(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'factories' => [
                    ServiceInterface::class => function (): ServiceInterface {
                        return new Service();
                    },
                ],
            ],
        ]);

        $instance = $container->get(ServiceInterface::class);

        $this->assertInstanceOf(ServiceInterface::class, $instance);
    }

    /**
     * Test combining factories with other dependency types.
     * Factories can coexist with services, invokables, etc.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoriesCombinedWithOtherTypes(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    'prebuilt.mailer' => new Mailer(),
                ],
                'invokables' => [
                    Service::class,
                ],
                'factories' => [
                    UserManager::class => function (ContainerInterface $container): UserManager {
                        return new UserManager($container->get('prebuilt.mailer'));
                    },
                ],
            ],
        ]);

        $this->assertInstanceOf(Mailer::class, $container->get('prebuilt.mailer'));
        $this->assertInstanceOf(Service::class, $container->get(Service::class));
        $this->assertInstanceOf(UserManager::class, $container->get(UserManager::class));
    }

    /**
     * Test factory that creates service with optional dependencies.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryWithOptionalDependencies(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'factories' => [
                    'optional.service' => function (ContainerInterface $container): Service {
                        $service = new Service();

                        // Only inject if available
                        if ($container->has('optional.dependency')) {
                            $service->inject($container->get('optional.dependency'));
                        }

                        return $service;
                    },
                ],
            ],
        ]);

        $service = $container->get('optional.service');

        $this->assertInstanceOf(Service::class, $service);
        $this->assertEmpty($service->getInjected());
    }

    /**
     * Helper method to create a container with the given configuration.
     *
     * @throws Exception
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    private function getContainer(array $config): Container
    {
        $factory = new ContainerFactory();
        $config = new Config($config);

        return $factory($config);
    }
}
