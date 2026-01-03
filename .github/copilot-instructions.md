# zend-phpdi-config: AI Coding Agent Instructions

## Project Overview

This library bridges Laminas/Mezzio service manager configuration with PHP-DI's PSR-11 container. It translates familiar `dependencies` array syntax into PHP-DI definitions while adding autowiring support.

**Key architecture**: [ContainerFactory.php](../src/ContainerFactory.php) instantiates a `ContainerBuilder`, [Config.php](../src/Config.php) translates service manager configuration into PHP-DI definitions (using `DI\autowire()`, `DI\create()`, `DI\factory()`, `DI\get()`), then builds the container.

## Core Patterns

### Configuration Translation in Config::addDefinitions()

The `dependencies` array keys map to PHP-DI definitions in a specific order (see [Config.php#L77-84](../src/Config.php#L77-L84)):

1. **services** → `DI\create()` or raw objects
2. **factories** → `DI\factory()` wrapping factory classes with `__invoke(ContainerInterface $container)`
3. **invokables** → `DI\create()` (numeric keys auto-create alias to class name itself)
4. **autowires** → `DI\autowire()` for dependency injection via constructor type-hints
5. **aliases** → `DI\get()` pointing to target service
6. **delegators** → Chained factory pattern using internal counter (`$delegatorCounter`) to create `$name-1`, `$name-2` entries

### Delegator Chain Implementation

Delegators wrap services via closure-based factories ([Config.php#L149-165](../src/Config.php#L149-L165)):
- Previous definition saved as `{serviceName}-{counter}`
- Factory receives `$container`, original `$name`, and `callable` returning previous definition
- Applied in array order (first delegator wraps original, second wraps first, etc.)

### Compilation & Caching Strategy

- **Compilation** ([Config.php#L54-62](../src/Config.php#L54-L62)): If `CompiledContainer.php` exists at `DI_CACHE_PATH`, skip definition building (early return)
  - Compilation pre-resolves all definitions at build time, making runtime resolution fast
  - **Autowires are optimized by compilation** - no performance penalty vs other definition types
- **Definition cache**: Separate APCu-based caching via `ENABLE_CACHE_DEFINITION`
- **Proxy writing**: `DI_PROXY_PATH` enables lazy loading proxies for dependency injection

### Performance Considerations

- **Use autowires freely** - with compilation enabled, autowired services perform identically to factories/invokables
- **Explicit registration recommended**: Even though unregistered classes can be autowired with no performance penalty:
  - Register application services in config for clarity and documentation
  - Makes service inventory visible in configuration files
  - Easier to customize, mock, or replace implementations
  - IDE-friendly for finding service definitions
- **Choose by semantics, not performance**: 
  - `autowires` for classes with typed constructor dependencies
  - `invokables` for classes with no/optional dependencies
  - `factories` for custom instantiation logic
  - `services` for pre-instantiated objects or scalar values
- Enable compilation (`DI_CACHE_PATH`) in production for optimal performance

## Testing Patterns

- All tests extend `PHPUnit\Framework\TestCase` (PHPUnit 11.x)
- Use `$this->getContainer($config)` helper to build containers in tests
- Test assets in [tests/TestAsset/](../tests/TestAsset/) follow service manager conventions:
  - Factories implement `__invoke(ContainerInterface $container): ServiceInterface`
  - Delegators implement `__invoke(ContainerInterface $container, string $name, callable $callback): Service`
- Mock `ContainerBuilder` to verify configuration methods (`enableDefinitionCache()`, `useAutowiring()`, etc.)

### Example Test Pattern
```php
$container = $this->getContainer([
    'dependencies' => [
        'factories' => [ServiceInterface::class => ServiceFactory::class],
    ],
]);
$this->assertInstanceOf(Service::class, $container->get(ServiceInterface::class));
```

## CLI Tool

`bin/add-autowires-entry` ([add-autowires-entry](../bin/add-autowires-entry)) uses [AutowiresConfigDumper](../src/Tool/AutowiresConfigDumper.php) to:
1. Parse existing PHP config file
2. Add class to `['dependencies']['autowires']` array (idempotent)
3. Pretty-print with `::class` constants for FQCNs

## Development Workflow

```bash
# Run tests (no coverage)
composer test

# Run with coverage (requires Xdebug)
composer test-coverage

# Run coverage + serve HTML report
composer cover  # Opens localhost:5001
```

## Key Constraints

- **PHP 8.2+ required** with strict types (`declare(strict_types=1)` everywhere)
- **Namespace**: `Elie\PHPDI\Config` (changed from `Zend\DI\Config` in v4.0)
- **No mixing definition types**: Each service name should appear in only ONE of services/factories/invokables/autowires/aliases
- **Factory return values**: Factories must return actual service instances, not service names
- **Test namespace**: `ElieTest\PHPDI\Config`

## Common Tasks

**Add new configuration option**: Update [ConfigInterface.php](../src/ConfigInterface.php) constants, implement in [Config.php](../src/Config.php) `configureContainer()` method, add test in [ConfigTest.php](../tests/ConfigTest.php).

**Handle new service type**: Add private method in [Config.php](../src/Config.php) called from `addDefinitions()`, use appropriate PHP-DI function (`autowire()`, `create()`, `factory()`, `get()`).

**CLI tool changes**: Modify [AutowiresConfigDumper.php](../src/Tool/AutowiresConfigDumper.php) for logic, [AutowiresConfigDumperCommand.php](../src/Tool/AutowiresConfigDumperCommand.php) for argument handling.
