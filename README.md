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

[zend-phpdi-config](https://packagist.org/packages/elie29/zend-phpdi-config) acts as a bridge to configure a PSR-11 compatible [PHP-DI](https://php-di.org) container using service manager configuration.

**Requirements:** PHP 8.2 or higher

It can be used with [Laminas](https://getlaminas.org/) and [Mezzio](https://docs.mezzio.dev/) starting from v10.0.0

This library uses autowiring technique, cache compilation and cache definitions as defined in [PHP-DI](https://php-di.org).

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

        // Write proxies to file : cf. https://php-di.org/doc/lazy-injection.html
        Config::DI_PROXY_PATH => __DIR__, // Folder path

        // Disable autowire (enabled by default)
        Config::USE_AUTOWIRE => false

        // Enable cache
        Config::ENABLE_CACHE_DEFINITION => false, // boolean, true if APCu is activated
    ])
);
```

The `dependencies` sub associative array can contain the following keys:

- `services`: an associative array that maps a key to a specific service instance or service name.
- `invokables`: an associative array that map a key to a constructor-less
  service; i.e., for services that do not require arguments to the constructor.
  The key and service name usually are the same; if they are not, the key is
  treated as an alias. It could also be an array of services.
- `autowires`: an array of service **with or without a constructor**;
  PHP-DI offers an autowire technique that will scan the code and see
  what are the parameters needed in the constructors.
  Any aliases needed should be created in the aliases configuration.
- `factories`: an associative array that maps a service name to a factory class
  name, or any callable. Factory classes must be instantiable without arguments,
  and callable once instantiated (i.e., implement the `__invoke()` method).
- `aliases`: an associative array that maps an alias to a service name (or
  another alias).
- `delegators`: an associative array that maps service names to lists of
  delegator factory keys, see the
  [Expressive delegators documentation](https://docs.laminas.dev/laminas-servicemanager/delegators/)
  for more details.

> **N.B.:** The whole configuration -- unless `dependencies` -- is merged in a `config` key within the `$container`:
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

Where UserManager depends on Mailer as follow:

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

To switch back to another container is very easy:

1. Create your factories with `__invoke` function
2. Replace `autowires` key in ConfigProvider by `factories` key, then for each class name attach its correspondent factory.

## PSR 11 and Interop\Container\ContainerInterface

V4.x supports as well Interop\Container\ContainerInterface

## Migration guides

- [Migration from 3.x to 4.0](docs/migration-4.0.md)
- Migration from 4.x to 5.0: container-interop/container-interop was dropped in favor of [PSR-11](https://packagist.org/packages/psr/container).

---

## 🤝 Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on how to contribute, run tests, and submit pull requests.

---

## 📄 License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.
