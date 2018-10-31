<?php

declare(strict_types = 1);

namespace Zend\DI\Config;

use DI\Container as DIContainer;
use Interop\Container\ContainerInterface as InteropContainer;

/**
 * Zend View is still using Interop\Container\ContainerInterface.
 * So this wrapper is to support this issue until zend service manger 4 is released.
 * @see https://github.com/elie29/zend-di-config/issues/25
 */
class ContainerWrapper extends DIContainer implements InteropContainer
{
}
