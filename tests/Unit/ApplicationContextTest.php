<?php

declare(strict_types=1);

namespace SuperKernel\Context\Tests\Unit;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use SuperKernel\Context\ApplicationContext;
use SuperKernel\Context\Exception\ContainerInitializationException;
use SuperKernel\Context\Exception\ContainerNotSetException;
use SuperKernel\Context\Tests\Fixture\Container;
use SuperKernel\Context\Tests\Fixture\Provider\DefaultContainerProvider;
use SuperKernel\Context\Tests\Fixture\Provider\InvalidDefaultContainerProvider;
use TypeError;

use function class_alias;
use function class_exists;

final class ApplicationContextTest extends TestCase
{
    protected function setUp(): void
    {
        self::resetContainer();
    }

    protected function tearDown(): void
    {
        self::resetContainer();
    }

    public function testCannotBeInstantiated(): void
    {
        self::assertFalse(new ReflectionClass(ApplicationContext::class)->isInstantiable());
    }

    public function testRaisesAnExplicitExceptionBeforeAContainerIsSet(): void
    {
        self::assertFalse(class_exists('SuperKernel\\Di\\Provider\\ContainerProvider'));
        $this->expectException(ContainerNotSetException::class);
        $this->expectExceptionMessageIsOrContains('The application context has no container.');

        ApplicationContext::getContainer();
    }

    public function testReturnsTheSetContainer(): void
    {
        $container = new Container();

        self::assertSame($container, ApplicationContext::setContainer($container));
        self::assertSame($container, ApplicationContext::getContainer());
    }

    public function testReplacesTheCurrentContainer(): void
    {
        $first = new Container();
        $second = new Container();

        ApplicationContext::setContainer($first);

        self::assertSame($second, ApplicationContext::setContainer($second));
        self::assertSame($second, ApplicationContext::getContainer());
    }

    /**
     * Runtime aliases supply optional dependencies only inside an isolated test process.
     *
     * @noinspection PhpIgnoredClassAliasDeclaration
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testInitializesAndCachesTheDefaultDiContainerWhenAvailable(): void
    {
        self::assertTrue(class_alias(
            DefaultContainerProvider::class,
            'SuperKernel\\Di\\Provider\\ContainerProvider',
        ));

        $container = ApplicationContext::getContainer();

        self::assertInstanceOf(Container::class, $container);
        self::assertSame($container, ApplicationContext::getContainer());
        self::assertSame(1, DefaultContainerProvider::$invocations);
    }

    /**
     * Runtime aliases supply optional dependencies only inside an isolated test process.
     *
     * @noinspection PhpIgnoredClassAliasDeclaration
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAnExplicitContainerTakesPriorityOverTheAvailableDefaultProvider(): void
    {
        self::assertTrue(class_alias(
            DefaultContainerProvider::class,
            'SuperKernel\\Di\\Provider\\ContainerProvider',
        ));
        $container = new Container();

        ApplicationContext::setContainer($container);

        self::assertSame($container, ApplicationContext::getContainer());
        self::assertSame(0, DefaultContainerProvider::$invocations);
    }

    /**
     * Runtime aliases supply optional dependencies only inside an isolated test process.
     *
     * @noinspection PhpIgnoredClassAliasDeclaration
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testPreservesAProviderFailureAndAllowsASecondInitializationAttempt(): void
    {
        self::assertTrue(class_alias(
            DefaultContainerProvider::class,
            'SuperKernel\\Di\\Provider\\ContainerProvider',
        ));
        DefaultContainerProvider::$fail = true;

        try {
            ApplicationContext::getContainer();
            self::fail('Expected default container initialization to fail.');
        } catch (ContainerInitializationException $exception) {
            self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
            self::assertSame('Default container initialization failed.', $exception->getPrevious()->getMessage());
        }

        DefaultContainerProvider::$fail = false;

        self::assertInstanceOf(Container::class, ApplicationContext::getContainer());
        self::assertSame(2, DefaultContainerProvider::$invocations);
    }

    /**
     * Runtime aliases supply optional dependencies only inside an isolated test process.
     *
     * @noinspection PhpIgnoredClassAliasDeclaration
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testWrapsAnInvalidDefaultContainerResult(): void
    {
        self::assertTrue(class_alias(
            InvalidDefaultContainerProvider::class,
            'SuperKernel\\Di\\Provider\\ContainerProvider',
        ));

        try {
            ApplicationContext::getContainer();
            self::fail('Expected the invalid container result to fail.');
        } catch (ContainerInitializationException $exception) {
            self::assertInstanceOf(TypeError::class, $exception->getPrevious());
        }
    }

    private static function resetContainer(): void
    {
        new ReflectionProperty(ApplicationContext::class, 'container')->setValue(null, null);
    }
}
