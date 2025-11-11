<?php
declare(strict_types=1);

namespace SuperKernel\Context;

use Psr\Container\ContainerInterface;

final class ApplicationContext
{
	private static ContainerInterface $container;

	public static function getContainer(): ContainerInterface
	{
		return self::$container;
	}

	public static function hasContainer(): bool
	{
		return isset(self::$container);
	}

	public static function setContainer(ContainerInterface $container): ContainerInterface
	{
		return self::$container = $container;
	}
}