<?php
declare(strict_types=1);

namespace SuperKernel\Context;

use Swoole\Coroutine;

/**
 * @template TValue
 */
final class Context
{
	/**
	 * @var array<string, TValue> $container
	 */
	private static array $container = [];

	/**
	 * @param string $id
	 * @param TValue $value
	 *
	 * @return TValue
	 */
	public static function set(string $id, mixed $value): mixed
	{
		self::$container[Coroutine::getCid()][$id] = $value;

		return $value;
	}

	/**
	 * @param string     $id
	 * @param TValue $default
	 *
	 * @return TValue
	 */
	public static function get(string $id, mixed $default = null): mixed
	{
		return self::$container[Coroutine::getCid()][$id] ?? $default;
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public static function has(string $id): bool
	{
		return array_key_exists($id, self::$container[Coroutine::getCid()] ?? []);
	}

	/**
	 * @param string|null $id
	 *
	 * @return void
	 */
	public static function delete(?string $id = null): void
	{
		$cid = Coroutine::getCid();

		if (null === $id) {
			unset(self::$container[$cid]);
			return;
		}

		unset(self::$container[$cid][$id]);
	}
}