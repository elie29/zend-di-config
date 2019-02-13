# zend-phpdi-config

[![Build Status](https://travis-ci.org/elie29/zend-di-config.svg?branch=master)](https://travis-ci.org/elie29/zend-di-config)
[![Coverage Status](https://coveralls.io/repos/github/elie29/zend-di-config/badge.svg)](https://coveralls.io/github/elie29/zend-di-config)

## Introduction
[zend-phpdi-config](https://packagist.org/packages/elie29/zend-phpdi-config) acts as a bridge to configure a PSR-11 compatible [PHP-DI](http://php-di.org) container using zend-servicemanager configuration.
It can be used with Zend Framework and Zend Expressive.

This library uses autowirng technique, cache compilation and cache definitions as defined in [PHP-DI](http://php-di.org).

## Configuration

[Service Manager Configuration](https://docs.zendframework.com/zend-servicemanager/configuring-the-service-manager/)

To get a configured [PSR-11](http://www.php-fig.org/psr/psr-11/)
PHP-DI container, do the following:

```php
<?php
use Zend\DI\Config\Config;
use Zend\DI\Config\ContainerFactory;

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
  [Expressive delegators documentation](https://docs.zendframework.com/zend-servicemanager/delegators/)
  for more details.

>**N.B.:** The whole configuration -- unless `dependencies` -- is merged in a `config` key within the `$container`:
>
>```php
>$config = $container->get('config');
>```

## CLI command to add a new autowire entry
![Configuration image](./config-add-entry.png)

The cli command `add-autowires-entry` creates the configuration file if it doesn't exist otherwise it adds the entry
to the autowires key.

Example of adding ConsoleHelper to a config.php:
>```console
>./vendor/bin/add-autowires-entry config.php "Zend\\Stdlib\\ConsoleHelper"
>[DONE] Changes written to config.php
>```

## Using with Expressive

Replace contents of `config/container.php` with the following:

```php
<?php

declare(strict_types = 1);

use Zend\DI\Config\Config;
use Zend\DI\Config\ContainerFactory;

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
V3.x supports as well Interop\Container\ContainerInterface because zend view is still using this interface.