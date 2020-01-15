# Migrating from version 3.x to 4.0

4.0 is a new major version that comes with backward compatibility breaks.

This guide will help you migrate from a 3.x version to 4.0.

## Breaking change

The only BC occurred in the namespace change.

Before we used `Zend\DI\Config`, starting from 4.0, the namespace becomes `Elie\PHPDI\Config`

> This decision is the conclusion of a discussion with [@thomasvargiu](https://github.com/thomasvargiu) within this issue [#28](https://github.com/elie29/zend-di-config/issues/28) and [Geert Eltink](https://github.com/xtreamwayz) through [#31](https://github.com/elie29/zend-di-config/issues/31).
> The purpose of this change, is to provide a new `definitions` key to the configuration in order to add specfic PHP-DI defintions. For more details, check this issue [#27](https://github.com/elie29/zend-di-config/issues/27).

## Using PHP-DI Container

In order to use the [PSR-11](http://www.php-fig.org/psr/psr-11/) container with [Zend Framework](https://framework.zend.com) or
[Zend Expressive](https://docs.zendframework.com/zend-expressive), you need to add the following code in a `container.php`
file as explained in README.md:

```php
<?php

declare(strict_types = 1);

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
