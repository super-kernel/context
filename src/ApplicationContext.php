<?php

declare(strict_types=1);

namespace SuperKernel\Context;

use Psr\Container\ContainerInterface;
use SuperKernel\Context\Exception\ContainerInitializationException;
use SuperKernel\Context\Exception\ContainerNotSetException;
use SuperKernel\Di\Provider\ContainerProvider;
use Throwable;

use function class_exists;
use function sprintf;

/**
 * Stores the process-local application container for bootstrap and infrastructure integration.
 */
final class ApplicationContext
{
    private static ?ContainerInterface $container = null;

    private function __construct()
    {
    }

    public static function setContainer(ContainerInterface $container): ContainerInterface
    {
        return self::$container = $container;
    }

    /**
     * @throws ContainerInitializationException when the available default provider cannot create a valid container
     * @throws ContainerNotSetException         when no container is set and the default DI provider is unavailable
     */
    public static function getContainer(): ContainerInterface
    {
        if (null !== self::$container) {
            return self::$container;
        }

        if (class_exists(ContainerProvider::class)) {
            try {
                return self::setContainer(new ContainerProvider()());
            } catch (Throwable $throwable) {
                throw new ContainerInitializationException(sprintf(
                    'Unable to initialize the application container through %s.',
                    ContainerProvider::class,
                ), 0, $throwable);
            }
        }

        throw new ContainerNotSetException('The application context has no container.');
    }
}
