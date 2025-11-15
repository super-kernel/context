<?php
declare(strict_types=1);

namespace SuperKernel\Context;

use Swoole\Coroutine;
use Swoole\Thread;
use Throwable;

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
			$tid = Thread::getId();
		}
		catch (Throwable) {
			$tid = -1;
		}

		if ($tid) {
			self::$container[$tid][$key] = $value;
		} else {
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
			$tid = Thread::getId();
		}
		catch (Throwable) {
			$tid = -1;
		}

		if ($tid) {
			return self::$container[$tid][$key] ?? $default;
		}

		return self::$container[Coroutine::getCid()][$key] ?? $default;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public static function has(string $key): bool
	{
		try {
			$tid = Thread::getId();
		}
		catch (Throwable) {
			$tid = -1;
		}

		if ($tid) {
			return array_key_exists($key, self::$container[$tid] ?? []);
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
		catch (Throwable) {
			$id = -1;
		}

		if (!$id) {
			$id = Coroutine::getCid();
		}

		if (null === $key) {
			unset(self::$container[$id]);
			return;
		}

		unset(self::$container[$id][$key]);
	}
}