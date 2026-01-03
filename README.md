# zend-phpdi-config

[![Build Status](https://github.com/elie29/zend-di-config/actions/workflows/php-build.yml/badge.svg)](https://github.com/elie29/zend-di-config/)
[![Coverage Status](https://coveralls.io/repos/github/elie29/zend-di-config/badge.svg)](https://coveralls.io/github/elie29/zend-di-config)
[![Packagist Downloads](https://img.shields.io/packagist/dt/elie29/zend-phpdi-config.svg)](https://packagist.org/packages/elie29/zend-phpdi-config)
[![PHP Version](https://img.shields.io/packagist/php-v/elie29/zend-phpdi-config.svg)](https://packagist.org/packages/elie29/zend-phpdi-config)

---

## 🚀 Quick Start

```bash
composer require elie29/zend-phpdi-config
```

```php
<?php

declare(strict_types=1);

use Elie\PHPDI\Config\Config;
use Elie\PHPDI\Config\ContainerFactory;

$container = (new ContainerFactory())(
    new Config(require __DIR__ . '/config/config.php')
);
```

---

## ✨ Features

- 🧩 PSR-11 compatible container configuration
- ⚡ Autowiring support
- 🔗 Service manager compatibility (Laminas/Mezzio)
- 💻 CLI tool for autowire entry management
- 🗃️ Cache and proxy support
- 🔄 Easy migration from other containers

---

## 📖 Introduction

[zend-phpdi-config](https://packagist.org/packages/elie29/zend-phpdi-config) acts as a bridge to configure a PSR-11
compatible [PHP-DI](https://php-di.org) container using service manager configuration.

**Requirements:** PHP 8.2 or higher

It can be used with [Laminas](https://getlaminas.org/) and [Mezzio](https://docs.mezzio.dev/) starting from v10.0.0

This library uses autowiring technique, cache compilation and cache definitions as defined
in [PHP-DI](https://php-di.org).

## 🛠️ Configuration

[Service Manager Configuration](https://docs.laminas.dev/laminas-servicemanager/configuring-the-service-manager/)

To get a configured [PSR-11](https://www.php-fig.org/psr/psr-11/)
PHP-DI container, do the following:

```php
<?php

declare(strict_types=1);

use Elie\PHPDI\Config\Config;
use Elie\PHPDI\Config\ContainerFactory;

$factory = new ContainerFactory();

$container = $factory(
    new Config([
        'dependencies' => [
            'services'   => [],
            'invokables' => [],
            'autowires'  => [], // A new key added to support PHP-DI autowire technique
            'factories'  => [],
            'aliases'    => [],
            'delegators' => [],
        ],
        // ... other configuration

        // Enable compilation
        Config::DI_CACHE_PATH => __DIR__, // Folder path

        // Write proxies to file: cf. https://php-di.org/doc/lazy-injection.html
        Config::DI_PROXY_PATH => __DIR__, // Folder path

        // Disable autowire (enabled by default)
        Config::USE_AUTOWIRE => false

        // Enable cache
        Config::ENABLE_CACHE_DEFINITION => false, // boolean, true if APCu is activated
    ])
);
```

### Configuration Keys

The `dependencies` configuration array supports the following keys:

- **`services`**: Maps a service name to a specific service instance, class name, or callable.
  - Values can be object instances, class names, or callables
  - Used for registering pre-instantiated services or simple service definitions
  - See [ServicesTest.php](tests/ServicesTest.php) for comprehensive examples

- **`invokables`**: Maps service names to classes with no constructor dependencies.
  - Can be an associative array (alias => class name) or indexed array (class names)
  - When alias differs from class name, the alias is created automatically
  - Classes must have no required constructor parameters
  - See [InvokablesTest.php](tests/InvokablesTest.php) for comprehensive examples

- **`autowires`**: Array of fully qualified class names to be autowired by PHP-DI.
  - PHP-DI automatically resolves constructor dependencies through type-hinting
  - Works with or without constructor parameters
  - Create aliases separately in the `aliases` configuration if needed

- **`factories`**: Maps service names to factory classes or callables.
  - Factory classes must implement `__invoke(ContainerInterface $container)`
  - Can reference other registered factories by name
  - Factories must return the actual service instance (object, array, scalar, etc.), not service names
  - Used when service instantiation requires custom or dynamic logic

- **`aliases`**: Maps alias names to service names or other aliases.
  - Allows multiple names to resolve to the same service instance
  - Can chain aliases (alias → alias → service)

- **`delegators`**: Maps service names to arrays of delegator factory classes.
  - Decorates or wraps services with additional functionality
  - Delegators are applied in the order specified
  - See [Laminas delegators documentation](https://docs.laminas.dev/laminas-servicemanager/delegators/) for details

> **N.B.:** All configuration except `dependencies` is merged into a `config` key within the `$container`:
>
> ```php
> $config = $container->get('config');
> ```

---

## 💻 CLI Usage

The CLI command `add-autowires-entry` creates the configuration file if it doesn't exist, otherwise it adds the entry
to the autowires key.

Example of adding ConsoleHelper to a config.php:

```console
./vendor/bin/add-autowires-entry config.php "Laminas\\Stdlib\\ConsoleHelper"
[DONE] Changes written to config.php
```

You can also add this as a Composer script:

```json
"scripts": {
"add-autowire": "add-autowires-entry config.php \"My\\Service\\Class\""
}
```

---

## 🧪 Development & Testing

### Running Tests

```bash
# Run tests (no coverage)
composer test

# Run with coverage (requires Xdebug)
composer test-coverage

# Run coverage + serve HTML report
composer cover  # Opens localhost:5001
```

### Git Hooks with GrumPHP

This project uses [GrumPHP](https://github.com/phpro/grumphp) to automatically run tests before each commit.

After running `composer install`, GrumPHP will:

- Install git hooks automatically
- Run `composer test` before every commit
- Block commits if tests fail

**Bypass the hook** (not recommended):

```bash
git commit --no-verify -m "Your message"
```

**Configure GrumPHP**: Edit [grumphp.yml](grumphp.yml) to customize tasks and behavior.

---

## 🐞 Troubleshooting / FAQ

**Q: My service is not autowired.**
A: Ensure it is listed in the `autowires` array and all dependencies are available.

**Q: The CLI tool fails with a permissions error.**
A: Make sure the config file directory is writable.

**Q: How do I debug container errors?**
A: Check that all dependencies are correctly defined and that your factories do not throw exceptions.

---

## Using with Mezzio (formerly Expressive)

Replace contents of `config/container.php` with the following:

```php
<?php

declare(strict_types=1);

use Elie\PHPDI\Config\Config;
use Elie\PHPDI\Config\ContainerFactory;

// Protect variables from global scope
return call_user_func(function () {

    $config = require __DIR__ . '/config.php';

    $factory = new ContainerFactory();

    // Container
    return $factory(new Config($config));
});

```

## Example of a ConfigProvider class

```php
<?php

declare(strict_types=1);

class ConfigProvider
{

    /**
     * Returns the configuration array
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies()
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'autowires' => [
                UserManager::class
            ]
        ];
    }
}
```

Where UserManager depends on Mailer as follows:

```php
<?php

declare(strict_types=1);

class UserManager
{
    private $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function register($email, $password)
    {
        $this->mailer->mail($email, 'Hello and welcome!');
    }
}

class Mailer
{
    public function mail($recipient, $content)
    {
    }
}

```

## Switching back to another container

To switch back to another container is straightforward:

1. Create your factories with `__invoke` function
2. Replace `autowires` key in ConfigProvider by `factories` key, then for each class name attach its correspondent
   factory.

## PSR 11 and Interop\Container\ContainerInterface

V4.x supports as well Interop\Container\ContainerInterface

## Migration guides

- [Migration from 3.x to 4.0](docs/migration-4.0.md)
- Migration from 4.x to 5.0: container-interop/container-interop was dropped in favor
  of [PSR-11](https://packagist.org/packages/psr/container).

---

## 🤝 Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on how to contribute, run tests, and submit pull requests.

---

## 📄 License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.
