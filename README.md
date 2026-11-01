<p align="right">
  <strong>English</strong> | <a href="README.zh-CN.md">中文文档</a>
</p>

# SuperKernel Context

`super-kernel/context` provides the process-local application container reference and a transparent execution-scope key-value context for SuperKernel. It does not require a concrete container implementation or the Swoole extension; when Swoole is present, its coroutine and thread identities provide isolated scopes. It accepts the optional Swoole extension in the `>=6.2.3 <6.3` range.

## Execution-scope contexts

Use `SuperKernel\Context\Context` for execution-scoped values:

```php
use SuperKernel\Context\Context;

Context::set('request-id', $requestId);
$requestId = Context::get('request-id');
```

The static entry point exposes:

- `get()`, `has()`, and `set()` for values in the current scope;
- `clear($key)` for one value and `clear()` for the current scope.

`Context` works without a container: it never accesses `ApplicationContext` or resolves a service. On every call it selects the first available scope in this priority:

1. Swoole coroutine;
2. Swoole thread;
3. process storage in the current PHP process.

Missing Swoole capabilities are skipped automatically, and the selection is not cached, so a later call may use a different scope. Native Fibers and Swow coroutines are not runtime scopes; outside an active Swoole scope they use the process storage.

Swoole stores coroutine values in its native context. Thread values are kept per Swoole thread identity, and process values for the lifetime of the PHP process. Clear values explicitly when their scope ends.

## Application container context

Set the canonical application container during bootstrap before application execution:

```php
use Psr\Container\ContainerInterface;
use SuperKernel\Context\ApplicationContext;

/** @var ContainerInterface $container */
ApplicationContext::setContainer($container);
```

Infrastructure code may then obtain that exact container:

```php
$container = ApplicationContext::getContainer();
```

`setContainer()` returns the supplied container and replaces any previously stored container. When `getContainer()` is called before an explicit container is set, it checks for the optional `SuperKernel\Di\Provider\ContainerProvider`. If available, the provider is constructed and invoked once, and its valid `Psr\Container\ContainerInterface` result becomes the current container. The Context package does not require `super-kernel/di`; when that provider is unavailable, `getContainer()` throws `SuperKernel\Context\Exception\ContainerNotSetException`.

An explicitly supplied PSR-11 container must resolve class and interface identifiers to compatible instances.

A default-provider failure is wrapped in `SuperKernel\Context\Exception\ContainerInitializationException` with the original failure retained as its previous exception. An invalid provider result raises the same Context exception. A failed attempt does not poison the application context, so a later call may retry or an explicit `setContainer()` may recover it.

Application services should continue to use explicit constructor or property injection instead of treating the context as a general-purpose service locator. Use the static `Context` entry point only for short-lived contextual values.
