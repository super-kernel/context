<p align="right">
  <a href="README.md">English</a> | <strong>中文文档</strong>
</p>

# SuperKernel Context

`super-kernel/context` 保存进程的应用容器，并为当前正在运行的代码存储短期值，每个 Swoole 协程和线程各自分开。

## 安装

```bash
composer require super-kernel/context
```

本包除 PHP 外没有其他要求。有 Swoole 扩展（`>=6.2.3 <6.3`）时，值按协程和线程分别保存。有 `super-kernel/di` 时，第一次请求容器会用 `skernel build` 的元数据创建它；没有它时，请自己设置一个 PSR-11 容器。

## 提供的能力

- `ApplicationContext::getContainer()`，返回进程的容器；安装了 `super-kernel/di` 时，在第一次使用时创建它。
- `ApplicationContext::setContainer()`，把进程的容器设置为任意 PSR-11 容器。
- `Context::get()`、`has()`、`set()` 和 `clear()`，以静态方式访问当前执行的值。
- 每个 Swoole 协程和 Swoole 线程各自的值，其他情况下为进程范围的值，每次调用都重新选择。

它不会：

- 取代注入：服务应当接收它们的依赖，而不是去查找它们；
- 在协程、请求或任务结束时清除值（Swoole 协程除外）；
- 为原生 Fiber 或 Swow 协程提供各自的值；
- 依赖某个容器实现。

## 快速开始

存储、读取并清除当前执行的值。将脚本保存为 `bin/context.php`：

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

运行脚本：

```bash
php bin/context.php
```

它会打印：

```text
string(5) "req-1"
bool(true)
string(2) "en"
bool(false)
```

## 公开接口

| 名称 | 类别 | 用途 |
| --- | --- | --- |
| `SuperKernel\Context\ApplicationContext` | 类 | `getContainer()` 返回进程的容器；`setContainer()` 替换它。 |
| `SuperKernel\Context\Context` | 类 | 静态的 `get()`、`has()`、`set()` 和 `clear()`，访问当前协程、线程或进程的值。 |

## 需要了解的行为

- 没有设置容器且没有安装 `super-kernel/di` 时，`getContainer()` 抛出 `SuperKernel\Context\Exception\ContainerNotSetException`，消息为 `The application context has no container.`。
- 创建容器失败时，`getContainer()` 抛出 `SuperKernel\Context\Exception\ContainerInitializationException`，消息为 `Unable to initialize the application container through SuperKernel\Di\Provider\ContainerProvider.`，并以失败原因作为其前一个异常（previous），例如缺少 `skernel build` 时的 `BuildRequiredException`。之后的调用会再次尝试。
- `Context` 优先使用当前的 Swoole 协程，然后是当前的 Swoole 线程，最后是进程。在协程之外存储的值会在进程的整个生命周期内保留，直到你清除它们。
- `has()` 对保存了 `null` 的键也报告存在；只有键不存在时，`get()` 才返回给定的默认值。

## 文档

开发者文档用完整示例（包括 Swoole 协程）介绍容器和执行值，以及不要这样做的事情：[SuperKernel Context](https://github.com/super-kernel/.github/blob/main/docs/components/context.zh-CN.md)。

## 许可证

基于 MIT 许可证发布，详见 [LICENSE](LICENSE)。
