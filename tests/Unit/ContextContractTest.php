<?php

declare(strict_types=1);

namespace SuperKernel\Context\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionType;
use SuperKernel\Context\Context;
use SuperKernel\Context\Context\CoroutineContext;
use SuperKernel\Context\Context\ProcessContext;
use SuperKernel\Context\Context\ThreadContext;
use SuperKernel\Context\Runtime\EnvironmentContextSelector;
use Throwable;

use function class_exists;
use function dirname;
use function file_get_contents;
use function interface_exists;
use function json_decode;
use function sort;

use const JSON_THROW_ON_ERROR;

final class ContextContractTest extends TestCase
{
    public function testRemovesContextInterfacesFactoriesAndDiProviders(): void
    {
        $componentRoot = dirname(__DIR__, 2);

        self::assertFalse(interface_exists('SuperKernel\Contract\ContextInterface'));

        self::assertFalse(interface_exists('SuperKernel\Context\Contract\ContextInterface'));
        self::assertFileDoesNotExist($componentRoot . '/src/Contract/ContextInterface.php');
        self::assertDirectoryDoesNotExist($componentRoot . '/src/Contract');

        self::assertFalse(class_exists('SuperKernel\Context\Factory\ContextFactory'));
        self::assertFileDoesNotExist($componentRoot . '/src/Factory/ContextFactory.php');
        self::assertDirectoryDoesNotExist($componentRoot . '/src/Factory');

        self::assertFalse(class_exists('SuperKernel\Context\Provider\ContextProvider'));
        self::assertFileDoesNotExist($componentRoot . '/src/Provider/ContextProvider.php');
    }

    public function testRemovesTheSeparateContextAdapterLayer(): void
    {
        $componentRoot = dirname(__DIR__, 2);

        self::assertFalse(interface_exists('SuperKernel\Context\Contract\ContextAdapterInterface'));
        self::assertFileDoesNotExist($componentRoot . '/src/Contract/ContextAdapterInterface.php');
        self::assertDirectoryDoesNotExist($componentRoot . '/src/Adapter');

        foreach ([
            'CoroutineContextAdapter',
            'FiberContextAdapter',
            'ProcessContextAdapter',
            'SwowContextAdapter',
            'ThreadContextAdapter',
        ] as $adapter) {
            self::assertFalse(class_exists('SuperKernel\Context\Adapter\\' . $adapter));
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testDeclaresTheDirectStaticContextApiWithFixedPriority(): void
    {
        $reflection = new ReflectionClass(Context::class);
        $methods = [];

        foreach ($reflection->getMethods() as $method) {
            $methods[] = $method->getName();
        }

        sort($methods);

        $contexts = $reflection->getReflectionConstant('CONTEXTS');

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isInstantiable());
        self::assertNull($reflection->getConstructor());
        self::assertSame([], $reflection->getInterfaceNames());
        self::assertSame([], $reflection->getAttributes());
        self::assertSame([], $reflection->getProperties());
        self::assertSame([
            CoroutineContext::class,
            ThreadContext::class,
            ProcessContext::class,
        ], $reflection->getConstant('CONTEXTS'));
        self::assertNotFalse($contexts);
        self::assertSame('array', (string) $contexts->getType());
        self::assertTrue($contexts->isPrivate());
        self::assertFalse($reflection->hasMethod('__call'));
        self::assertFalse($reflection->hasMethod('__callStatic'));
        self::assertSame([
            'clear',
            'get',
            'has',
            'set',
        ], $methods);

        foreach ([
            'get',
            'has',
            'set',
            'clear',
        ] as $operation) {
            $method = $reflection->getMethod($operation);

            self::assertTrue($method->isPublic());
            self::assertTrue($method->isStatic());
        }

        $selectorClass = new ReflectionClass(EnvironmentContextSelector::class);
        $selector = $selectorClass->getMethod('select');

        self::assertStringContainsString('@internal', (string) $selectorClass->getDocComment());
        self::assertTrue($selector->isStatic());
        self::assertStringContainsString('class-string', (string) $selector->getDocComment());

        self::assertContextOperationSignatures($reflection);

        $source = file_get_contents((string) $reflection->getFileName());

        self::assertIsString($source);
        self::assertStringNotContainsString('ApplicationContext', $source);
        self::assertStringNotContainsString('ContextFactory', $source);
        self::assertStringNotContainsString('ContextInterface', $source);
        self::assertStringNotContainsString('__callStatic', $source);
        self::assertStringNotContainsString('@method static', $source);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testEveryEnvironmentContextIsAStandaloneStaticClass(): void
    {
        $contexts = [
            CoroutineContext::class,
            ThreadContext::class,
            ProcessContext::class,
        ];

        foreach ($contexts as $context) {
            $reflection = new ReflectionClass($context);

            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->isInstantiable());
            self::assertNull($reflection->getConstructor());
            self::assertFalse($reflection->getParentClass());
            self::assertSame([], $reflection->getInterfaceNames());
            self::assertSame([], $reflection->getAttributes());

            foreach ($reflection->getProperties() as $property) {
                self::assertTrue($property->isStatic());
            }

            foreach ($reflection->getMethods() as $method) {
                self::assertTrue($method->isStatic());
            }

            foreach ([
                'support',
                'get',
                'has',
                'set',
                'clear',
            ] as $operation) {
                self::assertTrue($reflection->getMethod($operation)->isPublic());
            }

            self::assertContextOperationSignatures($reflection);
        }
    }

    public function testRemovesUnsupportedFiberAndSwowContexts(): void
    {
        $componentRoot = dirname(__DIR__, 2);

        self::assertFalse(class_exists('SuperKernel\Context\Context\FiberContext'));
        self::assertFalse(class_exists('SuperKernel\Context\Context\SwowContext'));
        self::assertFileDoesNotExist($componentRoot . '/src/Context/FiberContext.php');
        self::assertFileDoesNotExist($componentRoot . '/src/Context/SwowContext.php');
        self::assertFileDoesNotExist($componentRoot . '/tests/Fixture/SwowCoroutineRuntime.php');
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testContextPackageDoesNotDependOnContractOrAttribute(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/composer.json');

        self::assertIsString($contents);

        /** @var array{repositories?: mixed, require: array<string, string>} $composer */
        $composer = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayNotHasKey('repositories', $composer);
        self::assertSame([
            'php'           => '>=8.4.27 <8.5',
            'psr/container' => '^2.0.2',
        ], $composer['require']);
    }

    /** @throws Throwable When fixture setup or a supplied test callback fails */
    private static function assertContextOperationSignatures(ReflectionClass $reflection): void
    {
        $get = $reflection->getMethod('get');
        $has = $reflection->getMethod('has');
        $set = $reflection->getMethod('set');
        $clear = $reflection->getMethod('clear');

        self::assertSame('string', self::typeName($get->getParameters()[0]->getType()));
        self::assertSame('mixed', self::typeName($get->getParameters()[1]->getType()));
        self::assertNull($get->getParameters()[1]->getDefaultValue());
        self::assertSame('mixed', self::typeName($get->getReturnType()));

        self::assertSame('string', self::typeName($has->getParameters()[0]->getType()));
        self::assertSame('bool', self::typeName($has->getReturnType()));

        self::assertSame('string', self::typeName($set->getParameters()[0]->getType()));
        self::assertSame('mixed', self::typeName($set->getParameters()[1]->getType()));
        self::assertSame('mixed', self::typeName($set->getReturnType()));

        self::assertSame('string', self::typeName($clear->getParameters()[0]->getType()));
        self::assertTrue($clear->getParameters()[0]->allowsNull());
        self::assertNull($clear->getParameters()[0]->getDefaultValue());
        self::assertSame('void', self::typeName($clear->getReturnType()));
    }

    private static function typeName(?ReflectionType $type): string
    {
        self::assertInstanceOf(ReflectionNamedType::class, $type);

        return $type->getName();
    }
}
