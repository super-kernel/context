<?php

declare(strict_types=1);

namespace SuperKernel\Context\Context;

use ArrayObject;
use SuperKernel\Context\Runtime\ActiveThread;
use SuperKernel\Context\Runtime\ThreadIdentity;

use function array_key_exists;

final class ThreadContext
{
    /** @var ArrayObject<int, array<string, mixed>>|null */
    private static ?ArrayObject $storages = null;

    public static function support(): bool
    {
        return null !== ThreadIdentity::detect();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $values = (self::$storages ??= new ArrayObject())[ActiveThread::id()] ?? [];

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, (self::$storages ??= new ArrayObject())[ActiveThread::id()] ?? []);
    }

    public static function set(string $key, mixed $value): mixed
    {
        $threadId = ActiveThread::id();
        $storages = self::$storages ??= new ArrayObject();
        $values = $storages[$threadId] ?? [];
        $values[$key] = $value;
        $storages[$threadId] = $values;

        return $value;
    }

    public static function clear(?string $key = null): void
    {
        $threadId = ActiveThread::id();
        $storages = self::$storages ??= new ArrayObject();

        if (!isset($storages[$threadId])) {
            return;
        }

        if (null === $key) {
            unset($storages[$threadId]);

            return;
        }

        $values = $storages[$threadId];

        unset($values[$key]);

        if ([] === $values) {
            unset($storages[$threadId]);

            return;
        }

        $storages[$threadId] = $values;
    }
}
