<?php

declare(strict_types=1);

namespace SuperKernel\Context\Context;

use LogicException;
use Swoole\Coroutine;
use Swoole\Coroutine\Context as SwooleCoroutineContext;
use Throwable;

use function array_key_exists;
use function class_exists;
use function is_array;

final class CoroutineContext
{
    private const string STORAGE_KEY = self::class;

    public static function support(): bool
    {
        if (!class_exists(Coroutine::class, false)) {
            return false;
        }

        try {
            return 0 <= Coroutine::getCid();
        } catch (Throwable) {
            return false;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $values = self::values(self::context());

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::values(self::context()));
    }

    public static function set(string $key, mixed $value): mixed
    {
        $context = self::context();
        $values = self::values($context);
        $values[$key] = $value;
        $context[self::STORAGE_KEY] = $values;

        return $value;
    }

    public static function clear(?string $key = null): void
    {
        $context = self::context();

        if (null === $key) {
            unset($context[self::STORAGE_KEY]);

            return;
        }

        $values = self::values($context);

        unset($values[$key]);

        if ([] === $values) {
            unset($context[self::STORAGE_KEY]);

            return;
        }

        $context[self::STORAGE_KEY] = $values;
    }

    private static function context(): SwooleCoroutineContext
    {
        if (!class_exists(Coroutine::class, false)) {
            throw new LogicException('The Swoole coroutine context requires the Swoole extension.');
        }

        $context = Coroutine::getContext();

        if (null === $context) {
            throw new LogicException('The Swoole coroutine context requires an active coroutine.');
        }

        return $context;
    }

    /** @return array<string, mixed> */
    private static function values(SwooleCoroutineContext $context): array
    {
        $values = $context[self::STORAGE_KEY] ?? [];

        return is_array($values) ? $values : [];
    }
}
