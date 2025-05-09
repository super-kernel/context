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
	private static ?ContainerInterface $container = null;

	public static function getContainer(): ContainerInterface
	{
		return self::$container;
	}

	public static function hasContainer(): bool
	{
		return isset(self::$container);
	}

	public static function setContainer(ContainerInterface $container): void
	{
		self::$container = $container;
	}
}