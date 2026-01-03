<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config;

use DateTime;
use Elie\PHPDI\Config\Config;
use Elie\PHPDI\Config\ConfigInterface;
use Elie\PHPDI\Config\ContainerFactory;
use ElieTest\PHPDI\Config\TestAsset\InvokableWithEmptyConstructor;
use ElieTest\PHPDI\Config\TestAsset\Mailer;
use ElieTest\PHPDI\Config\TestAsset\Service;
use ElieTest\PHPDI\Config\TestAsset\ServiceFactory;
use ElieTest\PHPDI\Config\TestAsset\ServiceInterface;
use ElieTest\PHPDI\Config\TestAsset\UnregisteredService;
use ElieTest\PHPDI\Config\TestAsset\UnregisteredServiceWithContainer;
use ElieTest\PHPDI\Config\TestAsset\UnregisteredServiceWithDependency;
use ElieTest\PHPDI\Config\TestAsset\UserManager;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Comprehensive test suite for services configuration.
 *
 * Services are pre-configured values that can be:
 * - Pre-instantiated objects (shared instances)
 * - Class names (to be instantiated by PHP-DI)
 * - Callables (functions/closures that return instances)
 * - Factory instances (objects that can be called as factories)
 * - Scalar values, arrays, or any other PHP value
 */
class ServicesTest extends TestCase
{
    /**
     * Test service registered as a pre-instantiated object.
     * The same instance is returned on every get() call (singleton behavior).
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testServiceAsPreInstantiatedObject(): void
    {
        $serviceInstance = new Service();
        $unique = $serviceInstance->getUnique();

        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    'my.service' => $serviceInstance,
                ],
            ],
        ]);

        $instance1 = $container->get('my.service');
        $instance2 = $container->get('my.service');

        // Should return the exact same instance
        $this->assertSame($serviceInstance, $instance1);
        $this->assertSame($instance1, $instance2);
        $this->assertSame($unique, $instance1->getUnique());
    }

    /**
     * Test service registered as a class name.
     * PHP-DI will instantiate the class using DI\create().
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testServiceAsClassName(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    'mailer.service' => Mailer::class,
                ],
            ],
        ]);

        $instance = $container->get('mailer.service');

        $this->assertInstanceOf(Mailer::class, $instance);
        $this->assertTrue($instance->mail('test@example.com', 'Welcome'));
    }

    /**
     * Test service using class name as both key and value.
     * Common pattern for registering a service by its class name.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testServiceWithClassNameAsKeyAndValue(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    Service::class => Service::class,
                ],
            ],
        ]);

        $instance = $container->get(Service::class);

        $this->assertInstanceOf(Service::class, $instance);
    }

    /**
     * Test service as a closure that returns an instance.
     * The closure is called each time the service is requested.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testServiceAsClosureReturningInstance(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    'service.closure' => function (): Service {
                        return new Service();
                    },
                ],
            ],
        ]);

        $instance = $container->get('service.closure');

        $this->assertInstanceOf(Service::class, $instance);
    }

    /**
     * Test service as a closure with container dependency injection.
     * The closure can receive the container and other services as parameters.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testServiceAsClosureWithContainerDependency(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    'mailer' => new Mailer(),
                    'user.manager' => function (ContainerInterface $container) {
                        return new UserManager($container->get('mailer'));
                    },
                ],
            ],
        ]);

        $userManager = $container->get('user.manager');

        $this->assertInstanceOf(UserManager::class, $userManager);
        $userManager->register('user@example.com'); // Should not throw
    }

    /**
     * Test service as a closure with autowired dependency.
     * PHP-DI can autowire parameters in closures when autowiring is enabled.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testServiceAsClosureWithAutowiredDependency(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    Mailer::class => new Mailer(),
                    'user.manager' => function (Mailer $mailer): UserManager {
                        return new UserManager($mailer);
                    },
                ],
            ],
        ]);

        $userManager = $container->get('user.manager');

        $this->assertInstanceOf(UserManager::class, $userManager);
        $userManager->register('autowired@example.com'); // Should not throw
    }

    /**
     * Test service as a factory instance.
     * A factory object can be registered as a service and used by other services.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testServiceAsFactoryInstance(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    'my.factory' => new ServiceFactory(),
                ],
            ],
        ]);

        $factoryInstance = $container->get('my.factory');

        $this->assertInstanceOf(ServiceFactory::class, $factoryInstance);
        // The factory itself is returned, not what it would create
    }

    /**
     * Test prototype pattern: Factory service to get new instances on each call.
     * PHP-DI removed the prototype scope, so use this pattern for non-shared instances.
     *
     * Register a factory as a service, then manually invoke it to get fresh instances.
     * This is the recommended way to get new instances on each call since PHP-DI 7.x
     * removed the SCOPE_PROTOTYPE feature.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testPrototypePatternUsingFactoryService(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    'service.factory' => new ServiceFactory(),
                ],
            ],
        ]);

        // Get the factory instance (shared)
        $factory = $container->get('service.factory');

        // Each invocation creates a NEW instance (prototype behavior)
        $instance1 = $factory($container);
        $instance2 = $factory($container);

        $this->assertInstanceOf(Service::class, $instance1);
        $this->assertInstanceOf(Service::class, $instance2);
        $this->assertNotSame($instance1, $instance2);

        // This pattern is useful when:
        // - You need a new instance each time
        // - The service has a mutable state
        // - You want to avoid shared instance side effects
    }

    /**
     * Test multiple services of different types in a single configuration.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testMultipleServicesOfDifferentTypes(): void
    {
        $mailerInstance = new Mailer();

        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    'mailer.instance' => $mailerInstance,
                    'service.class' => Service::class,
                    'service.closure' => function (): InvokableWithEmptyConstructor {
                        return new InvokableWithEmptyConstructor();
                    },
                ],
            ],
        ]);

        $this->assertSame($mailerInstance, $container->get('mailer.instance'));
        $this->assertInstanceOf(Service::class, $container->get('service.class'));
        $this->assertInstanceOf(InvokableWithEmptyConstructor::class, $container->get('service.closure'));
    }

    /**
     * Test that object services are shared (singleton) by default.
     * The same instance is returned on multiple get() calls.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testObjectServicesAreSharedByDefault(): void
    {
        $original = new Service();

        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    'shared.service' => $original,
                ],
            ],
        ]);

        $instance1 = $container->get('shared.service');
        $instance2 = $container->get('shared.service');

        $this->assertSame($original, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test service referencing another service via closure.
     * Services can depend on other registered services.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testServiceReferencingAnotherService(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    'base.service' => new Service(),
                    'wrapper.service' => function (ContainerInterface $container): Service {
                        $base = $container->get('base.service');
                        $base->inject('wrapped');
                        return $base;
                    },
                ],
            ],
        ]);

        $wrapper = $container->get('wrapper.service');
        $base = $container->get('base.service');

        // Both should be the same instance since base.service is shared
        $this->assertSame($base, $wrapper);
        $this->assertContains('wrapped', $wrapper->getInjected());
    }

    /**
     * Test service with a complex object (DateTime).
     * Any PHP object can be registered as a service.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testServiceAsComplexObject(): void
    {
        $dateTime = new DateTime('2026-01-03 12:00:00');

        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    'app.startTime' => $dateTime,
                ],
            ],
        ]);

        $retrieved = $container->get('app.startTime');

        $this->assertSame($dateTime, $retrieved);
        $this->assertSame('2026-01-03 12:00:00', $retrieved->format('Y-m-d H:i:s'));
    }

    /**
     * Test service using interface as a key with implementation as a value.
     * Common pattern for interface-to-implementation mapping.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testServiceWithInterfaceAsKey(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'services' => [
                    ServiceInterface::class => new Service(),
                ],
            ],
        ]);

        $instance1 = $container->get(ServiceInterface::class);
        $instance2 = $container->get(Service::class);

        $this->assertInstanceOf(ServiceInterface::class, $instance1);
        // It does not create an alias for Service::class
        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test that PHP-DI can resolve unregistered classes with no constructor.
     * When autowiring is enabled (default), PHP-DI can automatically instantiate
     * classes that are not explicitly registered in the configuration.
     *
     * Note: With compilation enabled, there is NO performance difference between
     * registered and unregistered classes. However, explicit registration is often
     * better for clarity, maintainability, and control.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testUnregisteredClassWithNoConstructor(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                // UnregisteredService is NOT registered
            ],
        ]);

        // PHP-DI can still resolve it because autowiring is enabled
        $instance = $container->get(UnregisteredService::class);

        $this->assertInstanceOf(UnregisteredService::class, $instance);
        $this->assertSame('UnregisteredService', $instance->getName());
    }

    /**
     * Test that explicitly registering a class provides more control and clarity.
     * Best practice: Register classes explicitly for better maintainability,
     * documentation, and control over instantiation, even though performance
     * is identical with compilation.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testExplicitRegistrationIsBetterPractice(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'autowires' => [
                    UnregisteredService::class, // Explicitly documented in config
                ],
            ],
        ]);

        $instance = $container->get(UnregisteredService::class);

        $this->assertInstanceOf(UnregisteredService::class, $instance);

        // Benefits of explicit registration:
        // 1. Configuration serves as documentation
        // 2. IDE can find all service definitions
        // 3. Easier to add configuration/customization later
        // 4. Clear separation between application services and libraries
        // 5. No performance penalty with compilation
    }

    /**
     * Test that PHP-DI can resolve unregistered classes with container dependency.
     * The container itself can be automatically injected.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testUnregisteredClassWithContainerDependency(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                // UnregisteredServiceWithContainer is NOT registered
            ],
        ]);

        // PHP-DI can resolve it and inject the container
        $instance = $container->get(UnregisteredServiceWithContainer::class);

        $this->assertInstanceOf(UnregisteredServiceWithContainer::class, $instance);
        $this->assertInstanceOf(ContainerInterface::class, $instance->getContainer());
        $this->assertSame($container, $instance->getContainer());
    }

    /**
     * Test that PHP-DI can resolve unregistered classes with resolvable dependencies.
     * If dependencies can also be autowired, the entire chain is resolved.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testUnregisteredClassWithResolvableDependency(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                // Neither UnregisteredServiceWithDependency nor Mailer are registered
            ],
        ]);

        // PHP-DI can resolve both the service and its dependency
        $instance = $container->get(UnregisteredServiceWithDependency::class);

        $this->assertInstanceOf(UnregisteredServiceWithDependency::class, $instance);
        $this->assertInstanceOf(Mailer::class, $instance->getMailer());
    }

    /**
     * Test that unregistered classes fail when autowiring is disabled.
     * Without autowiring, only explicitly registered services can be resolved.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testUnregisteredClassFailsWhenAutowiringDisabled(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                // UnregisteredService is NOT registered
            ],
            ConfigInterface::USE_AUTOWIRE => false,
        ]);

        $this->expectException(NotFoundExceptionInterface::class);

        // This should fail because autowiring is disabled
        $container->get(UnregisteredService::class);
    }

    /**
     * Helper method to create a container with the given configuration.
     *
     * @throws Exception
     */
    private function getContainer(array $config): ContainerInterface
    {
        $factory = new ContainerFactory();
        $config = new Config($config);

        return $factory($config);
    }
}
