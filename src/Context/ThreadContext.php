<?php

declare(strict_types=1);

namespace SuperKernel\Context\Context;

use ArrayObject;
use LogicException;
use Swoole\Thread;
use Throwable;

use function array_key_exists;
use function class_exists;

final class ThreadContext
{
    /** @var ArrayObject<int, array<string, mixed>>|null */
    private static ?ArrayObject $storages = null;

    public static function support(): bool
    {
        return null !== self::threadId();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $values = self::storages()[self::currentThreadId()] ?? [];

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::storages()[self::currentThreadId()] ?? []);
    }

    public static function set(string $key, mixed $value): mixed
    {
        $threadId = self::currentThreadId();
        $storages = self::storages();
        $values = $storages[$threadId] ?? [];
        $values[$key] = $value;
        $storages[$threadId] = $values;

        return $value;
    }

    public static function clear(?string $key = null): void
    {
        $threadId = self::currentThreadId();
        $storages = self::storages();

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

    private static function currentThreadId(): int
    {
        $threadId = self::threadId();

        if (null === $threadId) {
            throw new LogicException('The Swoole thread context requires an active Swoole thread.');
        }

        return $threadId;
    }

    private static function threadId(): ?int
    {
        if (!class_exists(Thread::class, false)) {
            return null;
        }

        try {
            $threadId = Thread::getId();

            return 0 <= $threadId ? $threadId : null;
        } catch (Throwable) {
            return null;
        }
    }

    /** @return ArrayObject<int, array<string, mixed>> */
    private static function storages(): ArrayObject
    {
        return self::$storages ??= new ArrayObject();
    }
}
