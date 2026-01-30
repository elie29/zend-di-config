# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## [10.0.0+] - PHP 8.2 & Modern Tooling Era

### V10.0.3 - TBD

- [#66](https://github.com/elie29/zend-di-config/issues/66) Compact the CHANGELOG file
- [#65](https://github.com/elie29/zend-di-config/issues/65) Update composer.lock to upgrade packages to latest versions

### V10.0.2 - 2026-01-03

- [#63](https://github.com/elie29/zend-di-config/issues/63) Update tests to use only PHPUnit
- Documentation improvements

### V10.0.1 - 2025-12-12

- PHPUnit v11 support
- Documentation fixes

### V10.0.0 - 2025-12-05

- [#61](https://github.com/elie29/zend-di-config/issues/61) **Major upgrade:** PHP 8.2 minimum + PHPUnit 10+ with updated dependency stack

---

## [9.0.0+] - PHP 8.1 & Modern Analysis

### V9.0.3 - 2025-12-05

- [#60](https://github.com/elie29/zend-di-config/pull/60) Minor dependencies update

### V9.0.2 - 2025-03-11

- [#59](https://github.com/elie29/zend-di-config/pull/59) Switched from PHPStan to Psalm for static analysis

### V9.0.1 - 2023-03-28

- [#57](https://github.com/elie29/zend-di-config/pull/57) Added PHP-DI 7.0 support

### V9.0.0 - 2022-09-24

- [#55](https://github.com/elie29/zend-di-config/issues/55) PHP 8.1 compatibility

---

## [8.x] - PHP 8 Era

### V8.1.1 - 2022-01-18

- [#52](https://github.com/elie29/zend-di-config/issues/52) Enhanced container compilation and delegators

### V8.1.0 - 2021-12-16

- [#49](https://github.com/elie29/zend-di-config/issues/49) Support for writing proxy classes to files

### V8.0.0 - 2021-03-23

- **Breaking Change:** PHP 8.0+ only (dropped support for PHP 7.x)
- [#46](https://github.com/elie29/zend-di-config/issues/46) Migration to PHP 8---

## [6.0+] & [7.x] - Pre-PHP-8 Maintenance Era

### V7.x (2020-2021)

- General maintenance and dependency updates during v7 lifecycle

### V6.0.1 - 2021-03-21

- Added `composer/package-versions-deprecated` for PHP 7.1 backward compatibility
- Test corrections and CI improvements

### V6.0.0 - 2020-01-15

- [#43](https://github.com/elie29/zend-di-config/issues/43) **Breaking Change:** Migrated from Zend Framework to Laminas framework

---

## [5.x] - PSR-11 Transition Era

### V5.0.0 - 2019-11-27

- [#42](https://github.com/elie29/zend-di-config/issues/42) Removed container-interop/container-interop dependency
- Full PSR-11 compliance

---

## [4.x] - Namespace & Delegator Refinement Era

### V4.0.5 - 2019-11-27

- [#41](https://github.com/elie29/zend-di-config/issues/41) Made delegator override behavior idempotent

### V4.0.4 - 2019-09-29

- [#40](https://github.com/elie29/zend-di-config/issues/40) Composer dependency updates

### V4.0.3 - 2019-02-13

- **Fixed:** [#38](https://github.com/elie29/zend-di-config/issues/38) Resolved circular dependency detection issue

### V4.0.2 - 2018-12-24

- **Added:** [#35](https://github.com/elie29/zend-di-config/issues/35) Option to disable autowire via configuration
- **Added:** [#34](https://github.com/elie29/zend-di-config/issues/34) PHP 7.3 support
- [#36](https://github.com/elie29/zend-di-config/issues/36) Composer updates and coding standards migration

### V4.0.1 - 2018-11-07

- **Breaking Change:** [#28](https://github.com/elie29/zend-di-config/issues/28) & [#31](https://github.com/elie29/zend-di-config/issues/31) Changed namespace from `Zend\Di` to `Elie\PHPDI\Config`
- See [migration guide](docs/migration-4.0.md) for upgrade details

---

## [3.0.x] - Autowire Foundation Era (2018)

### V3.0.9 - 2018-10-31

- **Added:** [#25](https://github.com/elie29/zend-di-config/issues/25) Support for Expressive Skeleton with PHP-DI
- [#26](https://github.com/elie29/zend-di-config/issues/26) Added visibility modifiers to constants

### V3.0.8 - 2018-10-19

- [#24](https://github.com/elie29/zend-di-config/issues/24) Enhanced `Config::configureContainer()` method

### V3.0.7 - 2018-10-16

- [#21](https://github.com/elie29/zend-di-config/issues/21) Added CONTRIBUTING.md document
- [#23](https://github.com/elie29/zend-di-config/issues/23) Composer dependency updates

### V3.0.6 - 2018-10-08

- [#22](https://github.com/elie29/zend-di-config/issues/22) Integrated PHP static analysis tool (PHPStan)

### V3.0.5 - 2018-09-12

- [#20](https://github.com/elie29/zend-di-config/issues/20) Made invokables support array format

### V3.0.4 - 2018-09-10

- [#19](https://github.com/elie29/zend-di-config/issues/19) Added CLI command documentation

### V3.0.3 - 2018-06-06

- [#17](https://github.com/elie29/zend-di-config/issues/17) CLI command documentation

### V3.0.2 - 2018-06-05

- [#16](https://github.com/elie29/zend-di-config/issues/16) Added .gitattributes for release packaging

### V3.0.1 - 2018-06-05

- [#14](https://github.com/elie29/zend-di-config/issues/14) Added .gitattributes file to exclude test files from release

### V3.0.0 - 2018-06-05

- **Added:** [#12](https://github.com/elie29/zend-di-config/issues/12) CLI command for adding autowires entries to configuration
- **Changed:** [#11](https://github.com/elie29/zend-di-config/issues/11) **Breaking Change:** `autowires` now accepts array format instead of key-value pairs; use `aliases` for mappings

---

## [2.0.x] - Foundation Era (2018)

### V2.0.2 - 2018-06-04

- [#9](https://github.com/elie29/zend-di-config/issues/9) Refactored `Config` class to use constant instead of string key

### V2.0.1 - 2018-06-02

- [#8](https://github.com/elie29/zend-di-config/issues/8) Updated composer dependencies
- [#7](https://github.com/elie29/zend-di-config/issues/7) Renamed test folder to follow PSR-4

### V2.0.0 - 2018-05-31

- **Added:** [#6](https://github.com/elie29/zend-di-config/issues/6) New `autowires` configuration key support
- **Added:** [#5](https://github.com/elie29/zend-di-config/issues/5) Travis CI coverage integration
- [#4](https://github.com/elie29/zend-di-config/issues/4) Enhanced Travis CI with code coverage reporting
- **Fixed:** [#3](https://github.com/elie29/zend-di-config/issues/3) `DI_CACHE_PATH` constant recognition
- **Fixed:** [#2](https://github.com/elie29/zend-di-config/issues/2) Added config key to definition array
- **Fixed:** [#1](https://github.com/elie29/zend-di-config/issues/1) Invokable class creation with autowire function
