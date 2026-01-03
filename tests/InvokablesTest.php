<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config;

use DI\Definition\Exception\InvalidDefinition;
use Elie\PHPDI\Config\Config;
use Elie\PHPDI\Config\ConfigInterface;
use Elie\PHPDI\Config\ContainerFactory;
use ElieTest\PHPDI\Config\TestAsset\InvokableWithContainerDependency;
use ElieTest\PHPDI\Config\TestAsset\InvokableWithEmptyConstructor;
use ElieTest\PHPDI\Config\TestAsset\InvokableWithNullableParameter;
use ElieTest\PHPDI\Config\TestAsset\InvokableWithOptionalParameters;
use ElieTest\PHPDI\Config\TestAsset\InvokableWithoutConstructor;
use ElieTest\PHPDI\Config\TestAsset\ServiceWithRequiredParameter;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Comprehensive test suite for invokables configuration.
 *
 * Invokables are classes that can be instantiated without constructor dependencies.
 * They are suitable for:
 * - Classes without constructors
 * - Classes with empty constructors
 * - Classes with constructors that have only optional parameters
 * - Classes with constructors that have only nullable parameters
 */
class InvokablesTest extends TestCase
{
    /**
     * Test an invokable class without any constructor.
     * This is the simplest form of invokable.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testInvokableWithoutConstructor(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'invokables' => [
                    InvokableWithoutConstructor::class,
                ],
            ],
        ]);

        $instance = $container->get(InvokableWithoutConstructor::class);

        $this->assertInstanceOf(InvokableWithoutConstructor::class, $instance);
        $this->assertSame('InvokableWithoutConstructor', $instance->getName());
    }

    /**
     * Test an invokable class with an empty constructor.
     * The constructor can perform initialization without requiring parameters.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testInvokableWithEmptyConstructor(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'invokables' => [
                    InvokableWithEmptyConstructor::class,
                ],
            ],
        ]);

        $instance = $container->get(InvokableWithEmptyConstructor::class);

        $this->assertInstanceOf(InvokableWithEmptyConstructor::class, $instance);
        $this->assertNotEmpty($instance->getId());
    }

    /**
     * Test invokable class with optional parameters.
     * All constructor parameters have default values.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testInvokableWithOptionalParameters(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'invokables' => [
                    InvokableWithOptionalParameters::class,
                ],
            ],
        ]);

        $instance = $container->get(InvokableWithOptionalParameters::class);

        $this->assertInstanceOf(InvokableWithOptionalParameters::class, $instance);
        $this->assertSame('default', $instance->getName());
        $this->assertSame(0, $instance->getCount());
    }

    /**
     * Test invokable class with nullable parameter.
     * Parameters with null defaults are acceptable for invokables.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testInvokableWithNullableParameter(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'invokables' => [
                    InvokableWithNullableParameter::class,
                ],
            ],
        ]);

        $instance = $container->get(InvokableWithNullableParameter::class);

        $this->assertInstanceOf(InvokableWithNullableParameter::class, $instance);
        $this->assertFalse($instance->hasValue());
        $this->assertNull($instance->getValue());
    }

    /**
     * Test invokable with alias mapping.
     * Demonstrates using a custom service name (alias) instead of a class name.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testInvokableWithAlias(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'invokables' => [
                    'my.custom.service' => InvokableWithoutConstructor::class,
                ],
            ],
        ]);

        $instance = $container->get('my.custom.service');

        $this->assertInstanceOf(InvokableWithoutConstructor::class, $instance);
        $this->assertSame('InvokableWithoutConstructor', $instance->getName());

        // When alias differs from the class name, the class itself is also registered!
        $directInstance = $container->get(InvokableWithoutConstructor::class);
        $this->assertInstanceOf(InvokableWithoutConstructor::class, $directInstance);
    }

    /**
     * Test multiple invokables in a single configuration.
     * Verifies that multiple invokables can be registered together.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testMultipleInvokables(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'invokables' => [
                    InvokableWithoutConstructor::class,
                    InvokableWithEmptyConstructor::class,
                    'optional.service' => InvokableWithOptionalParameters::class,
                ],
            ],
        ]);

        $this->assertInstanceOf(
            InvokableWithoutConstructor::class,
            $container->get(InvokableWithoutConstructor::class)
        );
        $this->assertInstanceOf(
            InvokableWithEmptyConstructor::class,
            $container->get(InvokableWithEmptyConstructor::class)
        );
        $this->assertInstanceOf(
            InvokableWithOptionalParameters::class,
            $container->get('optional.service')
        );
    }

    /**
     * Test that each call to get() returns the same instance.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testInvokablesAreNotShared(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'invokables' => [
                    InvokableWithEmptyConstructor::class,
                ],
            ],
        ]);

        $instance1 = $container->get(InvokableWithEmptyConstructor::class);
        $instance2 = $container->get(InvokableWithEmptyConstructor::class);

        $this->assertSame($instance1, $instance2);
        $this->assertSame($instance1->getId(), $instance2->getId());
    }

    /**
     * Test numeric key behavior.
     * When using numeric keys, the class name serves as both key and value.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testInvokableWithNumericKey(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'invokables' => [
                    InvokableWithoutConstructor::class,
                    InvokableWithEmptyConstructor::class,
                ],
            ],
        ]);

        // Services are registered by their class name when using numeric keys
        $this->assertInstanceOf(
            InvokableWithoutConstructor::class,
            $container->get(InvokableWithoutConstructor::class)
        );
        $this->assertInstanceOf(
            InvokableWithEmptyConstructor::class,
            $container->get(InvokableWithEmptyConstructor::class)
        );
    }

    /**
     * Test that invokable with required parameters fails.
     * This demonstrates that invokables are NOT suitable for classes with required dependencies.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testInvokableWithRequiredParameterThrowsException(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'invokables' => [
                    ServiceWithRequiredParameter::class,
                ],
            ],
        ]);

        $this->expectException(InvalidDefinition::class);

        // This should fail because the class requires a parameter
        $container->get(ServiceWithRequiredParameter::class);
    }

    /**
     * Test invokable with a self-referencing alias pattern.
     * When alias and target are the same, only one definition is created.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testInvokableWithSelfReferencingAlias(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'invokables' => [
                    InvokableWithoutConstructor::class => InvokableWithoutConstructor::class,
                ],
            ],
        ]);

        $instance = $container->get(InvokableWithoutConstructor::class);

        $this->assertInstanceOf(InvokableWithoutConstructor::class, $instance);
    }

    /**
     * Test invokable with container dependency fails even when autowiring is enabled.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testInvokableWithContainerDependencyFailsWhenAutowiringDisabled(): void
    {
        $container = $this->getContainer([
            'dependencies' => [
                'invokables' => [
                    InvokableWithContainerDependency::class,
                ],
            ],
            ConfigInterface::USE_AUTOWIRE => true,
        ]);

        $this->expectException(InvalidDefinition::class);

        // This should fail because autowiring is disabled and the container cannot be resolved
        $container->get(InvokableWithContainerDependency::class);
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
