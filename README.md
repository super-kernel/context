<p align="right">
  <strong>English</strong> | <a href="README.zh-CN.md">中文文档</a>
</p>

# SuperKernel Context

`super-kernel/context` keeps the application container of a process and stores short-lived values for the code that is running now, separately for each Swoole coroutine and thread.

## Installation

```bash
composer require super-kernel/context
```

The package has no requirement beyond PHP. With the Swoole extension (`>=6.2.3 <6.3`), values are kept per coroutine and per thread. With `super-kernel/di`, the first request for the container creates it from the metadata of `skernel build`; without it, set a PSR-11 container yourself.

## What it provides

- `ApplicationContext::getContainer()`, which returns the process's container and creates it on first use when `super-kernel/di` is installed.
- `ApplicationContext::setContainer()`, which sets the process's container to any PSR-11 container.
- `Context::get()`, `has()`, `set()`, and `clear()`, static access to values of the current execution.
- Separate values for each Swoole coroutine and Swoole thread, and process-wide values elsewhere, chosen anew on every call.

It does not:

- replace injection: services should receive their dependencies, not look them up;
- clear values when a coroutine, request, or job ends, outside Swoole coroutines;
- give native Fibers or Swow coroutines values of their own;
- require a container implementation.

## Quick start

Store, read, and clear values of the current execution. Save the script as `bin/context.php`:

```php
<?php

declare(strict_types=1);

use SuperKernel\Context\Context;

require dirname(__DIR__) . '/vendor/autoload.php';

Context::set('request-id', 'req-1');
Context::set('user', null);

var_dump(Context::get('request-id'));
var_dump(Context::has('user'), Context::get('locale', 'en'));

Context::clear();

var_dump(Context::has('request-id'));
```

Run the script:

```bash
php bin/context.php
```

It prints:

```text
string(5) "req-1"
bool(true)
string(2) "en"
bool(false)
```

## Public surface

| Name | Kind | Purpose |
| --- | --- | --- |
| `SuperKernel\Context\ApplicationContext` | Class | `getContainer()` returns the process's container; `setContainer()` replaces it. |
| `SuperKernel\Context\Context` | Class | Static `get()`, `has()`, `set()`, and `clear()` for values of the current coroutine, thread, or process. |

## Behavior to know

- `getContainer()` raises `SuperKernel\Context\Exception\ContainerNotSetException` with the message `The application context has no container.` when no container was set and `super-kernel/di` is not installed.
- When creating the container fails, `getContainer()` raises `SuperKernel\Context\Exception\ContainerInitializationException` with the message `Unable to initialize the application container through SuperKernel\Di\Provider\ContainerProvider.` and the cause as its previous exception, such as the `BuildRequiredException` of a missing `skernel build`. A later call tries again.
- `Context` uses the current Swoole coroutine first, then the current Swoole thread, then the process. Values stored outside a coroutine stay for the life of the process until you clear them.
- `has()` reports a key that holds `null`; `get()` returns the given default only for a missing key.

## Documentation

The developer documentation describes the container and the execution values with complete examples, including Swoole coroutines, and what not to do: [SuperKernel Context](https://github.com/super-kernel/.github/blob/main/docs/components/context.md).

## License

Released under the MIT License. See [LICENSE](LICENSE).
