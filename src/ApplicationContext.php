<?php
declare(strict_types=1);

namespace SuperKernel\Context;

use Psr\Container\ContainerInterface;
use TypeError;

final class ApplicationContext
{
	private static ContainerInterface $container;

	/**
	 * @throws TypeError
	 */
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
		if (!isset(self::$container)) {
			self::$container = $container;
		}
		return $container;
	}
}