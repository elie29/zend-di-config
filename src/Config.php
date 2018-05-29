<?php

declare(strict_types = 1);

namespace Zend\DI\Config;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
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
        $this->definitions = $config;
    }

    public function configureContainer(ContainerBuilder $builder): void
    {
        $this->setDependencies();
        $this->addDefinitions($builder);
        $this->enableCompilation($builder);
        $this->enableCache($builder);
    }

    private function setDependencies(): void
    {
        $this->dependencies = $this->definitions['dependencies'] ?? [];
    }

    private function addDefinitions(ContainerBuilder $builder): void
    {
        $this->addServices();
        $this->addFactories();
        $this->addInvokables();
        $this->addAliases();
        $this->addDelegators();

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
        foreach ($this->get('invokables') as $name => $object) {
            $this->definitions[$name] = create($object);
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

    private function enableCompilation(ContainerBuilder $builder): void
    {
        if (! empty($this->definitions[static::DI_CACHE_PATH])) {
            $builder->enableCompilation($this->definitions[static::DI_CACHE_PATH]);
        }
    }

    private function enableCache(ContainerBuilder $builder): void
    {
        if (! empty($this->definitions[static::ENABLE_CACHE_DEFINITION])) {
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
