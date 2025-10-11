<?php
declare(strict_types=1);

namespace SuperKernel\Context;

use Swoole\Coroutine;
use Swoole\Thread;
use TypeError;

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
	 * @param string $key
	 * @param TValue $value
	 *
	 * @return TValue
	 */
	public static function set(string $key, mixed $value): mixed
	{
		try {
			self::$container[Thread::getId()][$key] = $value;
		}
		catch (TypeError) {
			self::$container[Coroutine::getCid()][$key] = $value;
		}

		return $value;
	}

	/**
	 * @param string $key
	 * @param TValue $default
	 *
	 * @return TValue
	 */
	public static function get(string $key, mixed $default = null): mixed
	{
		try {
			return self::$container[Thread::getId()][$key] ?? $default;
		}
		catch (TypeError) {
			return self::$container[Coroutine::getCid()][$key] ?? $default;
		}
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public static function has(string $key): bool
	{
		try {
			return array_key_exists($key, self::$container[Thread::getId()] ?? []);
		}
		catch (TypeError) {
		}

		return array_key_exists($key, self::$container[Coroutine::getCid()] ?? []);
	}

	/**
	 * @param string|null $key
	 *
	 * @return void
	 */
	public static function delete(?string $key = null): void
	{
		try {
			$id = Thread::getId();
		}
		catch (TypeError) {
			$id = Coroutine::getCid();
		}

		if (null === $key) {
			unset(self::$container[$id]);
			return;
		}

		unset(self::$container[$id][$key]);
	}
}