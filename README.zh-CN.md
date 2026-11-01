<p align="right">
  <a href="README.md">English</a> | <strong>中文文档</strong>
</p>

# SuperKernel Context

`super-kernel/context` 为 SuperKernel 提供进程内的应用容器引用，以及透明的执行作用域键值上下文。它不要求具体的容器实现，也不要求 Swoole 扩展；安装了 Swoole 时，由协程和线程身份提供相互隔离的作用域。它接受 `>=6.2.3 <6.3` 范围内的可选 Swoole 扩展。

## 执行作用域上下文

使用 `SuperKernel\Context\Context` 存放执行作用域内的值：

```php
use SuperKernel\Context\Context;

Context::set('request-id', $requestId);
$requestId = Context::get('request-id');
```

这个静态入口提供：

- `get()`、`has()` 和 `set()`，用于当前作用域内的值；
- `clear($key)` 清除一个值，`clear()` 清除当前作用域。

`Context` 不依赖容器：它从不访问 `ApplicationContext`，也不解析任何服务。每次调用时，它按以下优先级选择第一个可用的作用域：

1. Swoole 协程；
2. Swoole 线程；
3. 当前 PHP 进程内的进程存储。

缺少的 Swoole 能力会被自动跳过，选择结果也不会缓存，因此之后的调用可能使用不同的作用域。原生 Fiber 和 Swow 协程不是运行时作用域；在活动的 Swoole 作用域之外，它们使用进程存储。

Swoole 把协程内的值存放在其原生上下文中。线程内的值按 Swoole 线程身份分别保存，进程内的值在 PHP 进程的整个生命周期内保留。作用域结束时请显式清除这些值。

## 应用容器上下文

在启动阶段、应用执行之前设置规范的应用容器：

```php
use Psr\Container\ContainerInterface;
use SuperKernel\Context\ApplicationContext;

/** @var ContainerInterface $container */
ApplicationContext::setContainer($container);
```

之后基础设施代码可以取得这个确切的容器：

```php
$container = ApplicationContext::getContainer();
```

`setContainer()` 返回传入的容器，并替换之前保存的任何容器。如果在显式设置容器之前调用 `getContainer()`，它会检查可选的 `SuperKernel\Di\Provider\ContainerProvider`。若该类可用，就构造并调用它一次，其返回的有效 `Psr\Container\ContainerInterface` 成为当前容器。Context 包不依赖 `super-kernel/di`；该提供者不可用时，`getContainer()` 抛出 `SuperKernel\Context\Exception\ContainerNotSetException`。

显式传入的 PSR-11 容器必须把类和接口标识解析为兼容的实例。

默认提供者的失败会被包装为 `SuperKernel\Context\Exception\ContainerInitializationException`，原始失败作为其前序异常保留。提供者返回无效结果时也会抛出同一个 Context 异常。失败的尝试不会污染应用上下文，之后的调用可以重试，也可以通过显式调用 `setContainer()` 恢复。

应用服务仍应使用显式的构造函数注入或属性注入，而不是把上下文当作通用的服务定位器。静态的 `Context` 入口只用于短生命周期的上下文值。
