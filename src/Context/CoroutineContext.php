<?php

declare(strict_types=1);

namespace SuperKernel\Context\Context;

use SuperKernel\Context\Runtime\ActiveCoroutine;
use SuperKernel\Context\Runtime\CoroutineValues;
use Swoole\Coroutine;
use Throwable;

use function array_key_exists;
use function class_exists;

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
        $values = CoroutineValues::read(ActiveCoroutine::context(), self::STORAGE_KEY);

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, CoroutineValues::read(ActiveCoroutine::context(), self::STORAGE_KEY));
    }

    public static function set(string $key, mixed $value): mixed
    {
        $context = ActiveCoroutine::context();
        $values = CoroutineValues::read($context, self::STORAGE_KEY);
        $values[$key] = $value;
        $context[self::STORAGE_KEY] = $values;

        return $value;
    }

    public static function clear(?string $key = null): void
    {
        $context = ActiveCoroutine::context();

        if (null === $key) {
            unset($context[self::STORAGE_KEY]);

            return;
        }

        $values = CoroutineValues::read($context, self::STORAGE_KEY);

        unset($values[$key]);

        if ([] === $values) {
            unset($context[self::STORAGE_KEY]);

            return;
        }

        $context[self::STORAGE_KEY] = $values;
    }
}
