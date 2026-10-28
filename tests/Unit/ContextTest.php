<?php

declare(strict_types=1);

namespace SuperKernel\Context\Tests\Unit;

use Error;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
use SuperKernel\Context\ApplicationContext;
use SuperKernel\Context\Context;
use SuperKernel\Context\Context\ProcessContext;
use SuperKernel\Context\Context\ThreadContext;
use SuperKernel\Context\Tests\Fixture\ThreadRuntime;
use Swoole\Coroutine as SwooleCoroutine;
use Swoole\Thread as SwooleThread;

use function class_alias;
use function class_exists;
use function Swoole\Coroutine\run;

final class ContextTest extends TestCase
{
    protected function setUp(): void
    {
        self::resetContexts();
    }

    protected function tearDown(): void
    {
        self::resetContexts();
    }

    public function testStoresAndClearsValuesInTheCurrentScope(): void
    {
        $value = new stdClass();

        self::assertFalse(Context::has('value'));
        self::assertSame('fallback', Context::get(key: 'value', default: 'fallback'));
        self::assertSame($value, Context::set('value', $value));
        self::assertSame(null, Context::set('nullable', null));
        self::assertTrue(Context::has('value'));
        self::assertTrue(Context::has('nullable'));
        self::assertSame($value, Context::get('value'));
        self::assertNull(Context::get('nullable', 'fallback'));

        Context::clear('value');

        self::assertFalse(Context::has('value'));
        self::assertTrue(Context::has('nullable'));

        Context::clear();

        self::assertFalse(Context::has('nullable'));
    }

    /**
     * Exercises PHP rejection of an intentionally absent operation.
     *
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testRejectsUnknownStaticOperations(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessageIsOrContains(
            'Call to undefined method SuperKernel\Context\Context::unsupported()',
        );

        Context::unsupported();
    }

    public function testIsolatesValuesBetweenSwooleCoroutines(): void
    {
        if (!class_exists(SwooleCoroutine::class, false)) {
            self::markTestSkipped('ext-swoole is unavailable.');
        }

        Context::set('scope', 'process');

        run(static function (): void {
            TestCase::assertSame('missing', Context::get('scope', 'missing'));
            Context::set('scope', 'parent');

            $child = SwooleCoroutine::create(static function (): void {
                TestCase::assertSame('missing', Context::get('scope', 'missing'));
                Context::set('scope', 'child');
                TestCase::assertSame('child', Context::get('scope'));
            });

            TestCase::assertIsInt($child);
            TestCase::assertSame('parent', Context::get('scope'));
        });

        self::assertSame('process', Context::get('scope'));
    }

    /**
     * Runtime aliases supply optional dependencies only inside an isolated test process.
     *
     * @noinspection PhpIgnoredClassAliasDeclaration
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUsesTheCurrentSwooleThreadIdentity(): void
    {
        if (class_exists(SwooleThread::class, false)) {
            self::markTestSkipped('The runtime already provides Swoole thread support.');
        }

        self::assertTrue(class_alias(ThreadRuntime::class, SwooleThread::class));
        ThreadRuntime::$id = 1;
        Context::set('scope', 'first');

        ThreadRuntime::$id = 2;
        self::assertSame('second', Context::get('scope', 'second'));
        Context::set('scope', 'second');

        ThreadRuntime::$id = 1;
        self::assertSame('first', Context::get('scope'));

        ThreadRuntime::$id = 2;
        self::assertSame('second', Context::get('scope'));
    }

    private static function resetContexts(): void
    {
        new ReflectionProperty(ApplicationContext::class, 'container')->setValue(null, null);
        new ReflectionProperty(ProcessContext::class, 'values')->setValue(null, []);
        new ReflectionProperty(ThreadContext::class, 'storages')->setValue(null, null);
    }
}
