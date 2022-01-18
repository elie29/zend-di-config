<?php

declare(strict_types=1);

namespace Elie\PHPDI\Config;

use DI\ContainerBuilder;
use DI\Definition\Helper\DefinitionHelper;
use Psr\Container\ContainerInterface;

use function DI\autowire;
use function DI\create;
use function DI\factory;
use function DI\get;
use function is_array;
use function is_file;
use function is_numeric;
use function is_object;
use function rtrim;

class Config implements ConfigInterface
{
    private array $definitions;

    private array $dependencies = [];

    /**
     * Make overridden delegator idempotent
     */
    private int $delegatorCounter = 0;

    public function __construct(array $config)
    {
        $this->definitions = [self::CONFIG => $config];
    }

    public function configureContainer(ContainerBuilder $builder): void
    {
        $this->shouldWriteProxiesToFile($builder);

        if (! $this->enableCompilation($builder)) {
            $this->setDependencies();
            $this->addDefinitions($builder);
        }

        $this->useAutowire($builder);
        $this->enableCache($builder);
    }

    /**
     * @return bool true if compilation is enabled and CompiledContainer exists.
     */
    private function enableCompilation(ContainerBuilder $builder): bool
    {
        $path = $this->definitions[self::CONFIG][self::DI_CACHE_PATH] ?? null;

        if ($path) {
            $builder->enableCompilation($path);
            return is_file(rtrim($path, '/') . '/CompiledContainer.php');
        }

        return false;
    }

    private function setDependencies(): void
    {
        $this->dependencies = $this->definitions[self::CONFIG]['dependencies'] ?? [];
    }

    private function addDefinitions(ContainerBuilder $builder): void
    {
        $this->addServices();
        $this->addFactories();
        $this->addInvokables();
        $this->addAutowires();
        $this->addAliases();
        $this->addDelegators();

        /**
         * PHPDI ArrayDefinition would resolve all keys
         * or dependencies are already resolved
         * (@see https://github.com/elie29/zend-di-config/issues/38)
         */
        unset($this->definitions[self::CONFIG]['dependencies']);
        $this->dependencies = [];

        $builder->addDefinitions($this->definitions);
    }

    private function addServices(): void
    {
        foreach ($this->get('services') as $name => $service) {
            $this->definitions[$name] = is_object($service) ? $service : create($service);
        }
    }

    private function addFactories(): void
    {
        foreach ($this->get('factories') as $name => $factory) {
            $this->definitions[$name] = factory($factory);
        }
    }

    private function addInvokables(): void
    {
        foreach ($this->get('invokables') as $key => $object) {
            $name = is_numeric($key) ? $object : $key;
            $this->addInvokable($name, $object);
        }
    }

    private function addInvokable(string $name, string $service): void
    {
        $this->definitions[$name] = create($service);
        if ($name !== $service) {
            // create an alias to the service itself
            $this->definitions[$service] = get($name);
        }
    }

    private function addAutowires(): void
    {
        foreach ($this->get('autowires') as $name) {
            $this->definitions[$name] = autowire($name);
        }
    }

    private function addAliases(): void
    {
        foreach ($this->get('aliases') as $alias => $target) {
            $this->definitions[$alias] = get((string) $target);
        }
    }

    private function addDelegators(): void
    {
        foreach ($this->get('delegators') as $name => $delegators) {
            foreach ($delegators as $delegator) {
                $previous                     = $name . '-' . ++$this->delegatorCounter;
                $this->definitions[$previous] = $this->definitions[$name];
                $this->definitions[$name]     = $this->createDelegatorFactory($delegator, $previous, $name);
            }
        }
    }

    private function createDelegatorFactory(string $delegator, string $previous, string $name): DefinitionHelper
    {
        return factory(function (
            ContainerInterface $container,
            string $delegator,
            string $previous,
            string $name
        ) {
            $factory  = new $delegator();
            $callable = function () use ($previous, $container) {
                return $container->get($previous);
            };
            return $factory($container, $name, $callable);
        })->parameter('delegator', $delegator)
          ->parameter('previous', $previous)
          ->parameter('name', $name);
    }

    private function useAutowire(ContainerBuilder $builder): void
    {
        // default autowire is true
        $autowire = $this->definitions[self::CONFIG][self::USE_AUTOWIRE] ?? true;
        $builder->useAutowiring($autowire);
    }

    private function enableCache(ContainerBuilder $builder): void
    {
        if (! empty($this->definitions[self::CONFIG][self::ENABLE_CACHE_DEFINITION])) {
            $builder->enableDefinitionCache();
        }
    }

    private function shouldWriteProxiesToFile(ContainerBuilder $builder): void
    {
        $path = $this->definitions[self::CONFIG][self::DI_PROXY_PATH] ?? null;
        if ($path) {
            $builder->writeProxiesToFile(true, $path);
        }
    }

    private function get(string $key): array
    {
        if (! isset($this->dependencies[$key]) || ! is_array($this->dependencies[$key])) {
            return [];
        }
        return $this->dependencies[$key];
    }
}
