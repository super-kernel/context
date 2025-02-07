<?php
declare(strict_types=1);

namespace SuperKernel\Context;

use Psr\Container\ContainerInterface;

/**
 * @ApplicationContext
 * @\SuperKernel\Context\ApplicationContext
 */
final class ApplicationContext
{
    static
    private ContainerInterface $container;

    static
    public function getContainer(): ContainerInterface
    {
        return self::$container;
    }

    static
    public function hasContainer(): bool
    {
        return isset(self::$container);
    }

    static
    public function setContainer(ContainerInterface $container): ContainerInterface
    {
        self::$container = $container;
        return $container;
    }
}