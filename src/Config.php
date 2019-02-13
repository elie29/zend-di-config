<?php

declare(strict_types = 1);

namespace Zend\DI\Config;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use function DI\autowire;
use function DI\create;
use function DI\factory;
use function DI\get;
use function is_array;

class Config implements ConfigInterface
{

    private $definitions = [];

    private $dependencies = [];

    public function __construct(array $config)
    {
        $this->definitions = [$this::CONFIG => $config];
    }

    public function configureContainer(ContainerBuilder $builder): void
    {
        if (! $this->enableCompilation($builder)) {
            $this->setDependencies();
            $this->addDefinitions($builder);
        }

        $this->useAutowire($builder);
        $this->enableCache($builder);
    }

    /**
     * @param ContainerBuilder $builder
     *
     * @return bool true if compilation is enabled and CompiledContainer exists.
     */
    private function enableCompilation(ContainerBuilder $builder): bool
    {
        $path = $this->definitions[$this::CONFIG][$this::DI_CACHE_PATH] ?? null;

        if ($path) {
            $builder->enableCompilation($path);
            return is_file(rtrim($path, '/') . '/CompiledContainer.php');
        }

        return false;
    }

    private function setDependencies(): void
    {
        $this->dependencies = $this->definitions[$this::CONFIG]['dependencies'] ?? [];
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
        unset($this->definitions[$this::CONFIG]['dependencies']);

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
            $this->definitions[$alias] = get($target);
        }
    }

    private function addDelegators(): void
    {
        foreach ($this->get('delegators') as $name => $delegators) {
            foreach ($delegators as $delegator) {
                $previous = uniqid($name, true);
                $this->definitions[$previous] = $this->definitions[$name];
                $callable = function (ContainerInterface $c) use ($delegator, $previous, $name) {
                    $factory = new $delegator();
                    $callable = function () use ($previous, $c) {
                        return $c->get($previous);
                    };
                    return $factory($c, $name, $callable);
                };
                $this->definitions[$name] = $callable;
            }
        }
    }

    private function useAutowire(ContainerBuilder $builder): void
    {
        // default autowire is true
        $autowire = $this->definitions[$this::CONFIG][$this::USE_AUTOWIRE] ?? true;
        $builder->useAutowiring($autowire);
    }

    private function enableCache(ContainerBuilder $builder): void
    {
        if (! empty($this->definitions[$this::CONFIG][$this::ENABLE_CACHE_DEFINITION])) {
            $builder->enableDefinitionCache();
        }
    }

    private function get($key): array
    {
        if (! isset($this->dependencies[$key]) || ! is_array($this->dependencies[$key])) {
            return [];
        }
        return $this->dependencies[$key];
    }
}
