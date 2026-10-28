# SuperKernel Context

`super-kernel/context` provides the process-local application container reference and a transparent execution-scope
key-value context for SuperKernel. It does not require a concrete container implementation or the Swoole extension;
when Swoole is present, its coroutine and thread identities provide isolated scopes. The package requires PHP 8.4.27 or later in the 8.4.x series
and accepts the optional Swoole extension in the `>=6.2.3 <6.3` range.

## Execution-scope contexts

Use `SuperKernel\Context\Context` for execution-scoped values:

```php
use SuperKernel\Context\Context;

Context::set('request-id', $requestId);
$requestId = Context::get('request-id');
```

Execution context is accessed through the static `Context` API, independently of application container access.

The static entry point exposes:

- `get()`, `has()`, and `set()` for values in the current scope;
- `clear($key)` for one value and `clear()` for the current scope.

`Context` is final and declares no constructor, instance operations, DI metadata, or service cache. It directly declares
the four public static operations, owns the ordered environment-context class list, and selects a supporting class on
every call. Unknown operations fail as ordinary undefined static methods. Execution-context calls never access
`ApplicationContext` or resolve a container service.

There is no Context interface, Context factory, or separate adapter layer. `Context` checks the environment contexts in
this priority:

1. Swoole coroutine;
2. Swoole thread;
3. process storage in the current PHP process.

This order prefers Swoole coroutine isolation before thread and process storage. Native Fibers and Swow coroutines are
not runtime scopes; outside an active Swoole scope they use the process fallback. Missing Swoole capabilities are
skipped automatically, and `ProcessContext` always supports the fallback environment. `Context` does not cache which
class matched, so a later call may select a different runtime scope. The static entry point and environment contexts
create no context service objects.

Runtime behavior is implemented separately by `CoroutineContext`, `ThreadContext`, and `ProcessContext` under
`SuperKernel\Context\Context`. Each standalone class exposes the same static `support()`, `get()`, `has()`, `set()`, and
`clear()` operations and combines its environment detection with its environment-specific static storage. The classes
declare no constructor, interface, instance property, or DI attribute. There is no shared abstract context or
`ContextStorage`, and callers do not select an environment context.

Swoole stores coroutine values in its native context. The thread context retains a static map keyed by Swoole thread
identity, and the process context retains a static value map for the PHP process. Clear values explicitly when their
scope ends. Native Fibers and Swow do not create independent context scopes.

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

`setContainer()` returns the supplied container and replaces any previously stored container. When `getContainer()` is
called before an explicit container is set, it checks for the optional
`SuperKernel\Di\Provider\ContainerProvider`. If available, the provider is constructed and invoked once, and its valid
`Psr\Container\ContainerInterface` result becomes the current container. The Context package does not require
`super-kernel/di`; when that provider is unavailable, `getContainer()` throws
`SuperKernel\Context\Exception\ContainerNotSetException`.

An explicitly supplied PSR-11 container must follow the project convention that class and interface identifiers
resolve to compatible instances.

A default-provider failure is wrapped in `SuperKernel\Context\Exception\ContainerInitializationException` with the
original failure retained as its previous exception. An invalid provider result raises the same Context exception. A
failed attempt does not poison the application context, so a later call may retry or an explicit `setContainer()` may
recover it.

Application services should continue to use explicit constructor or property injection instead of treating the context
as a general-purpose service locator. Use the static `Context` entry point only for short-lived contextual values.

## Development

Use a PHP 8.4 Composer in this package's checkout:

```bash
composer install
composer validate --strict
composer test
composer format:check
composer format
```
