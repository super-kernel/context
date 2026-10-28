<?php

declare(strict_types=1);

namespace SuperKernel\Context\Tests\Unit;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use SuperKernel\Context\Context\CoroutineContext;
use SuperKernel\Context\Context\ProcessContext;
use SuperKernel\Context\Context\ThreadContext;
use SuperKernel\Context\Tests\Fixture\ThreadRuntime;
use Swoole\Coroutine as SwooleCoroutine;
use Swoole\Thread as SwooleThread;

use function class_alias;
use function class_exists;
use function Swoole\Coroutine\run;

final class EnvironmentContextTest extends TestCase
{
    public function testProcessContextAlwaysSupportsTheCurrentProcess(): void
    {
        self::assertTrue(ProcessContext::support());
    }

    public function testSwooleCoroutineContextSupportsOnlyAnActiveCoroutine(): void
    {
        if (!class_exists(SwooleCoroutine::class, false)) {
            self::markTestSkipped('ext-swoole is unavailable.');
        }

        $supported = false;

        self::assertFalse(CoroutineContext::support());

        run(static function () use (&$supported): void {
            $supported = CoroutineContext::support();
        });

        self::assertTrue($supported);
        self::assertFalse(CoroutineContext::support());
    }

    /**
     * Runtime aliases supply optional dependencies only inside an isolated test process.
     *
     * @noinspection PhpIgnoredClassAliasDeclaration
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSwooleThreadContextSupportsAValidThreadIdentity(): void
    {
        if (class_exists(SwooleThread::class, false)) {
            self::markTestSkipped('The runtime already provides Swoole thread support.');
        }

        self::assertFalse(ThreadContext::support());
        self::assertTrue(class_alias(ThreadRuntime::class, SwooleThread::class));

        ThreadRuntime::$id = -1;

        self::assertFalse(ThreadContext::support());

        ThreadRuntime::$id = 1;

        self::assertTrue(ThreadContext::support());
    }

}
