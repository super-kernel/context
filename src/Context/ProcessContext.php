<?php

declare(strict_types=1);

namespace SuperKernel\Context\Context;

use function array_key_exists;

final class ProcessContext
{
    /** @var array<string, mixed> */
    private static array $values = [];

    public static function support(): bool
    {
        return true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, self::$values) ? self::$values[$key] : $default;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$values);
    }

    public static function set(string $key, mixed $value): mixed
    {
        self::$values[$key] = $value;

        return $value;
    }

    public static function clear(?string $key = null): void
    {
        if (null === $key) {
            self::$values = [];

            return;
        }

        unset(self::$values[$key]);
    }
}
