<?php

declare(strict_types=1);

namespace SuperKernel\Context;

use SuperKernel\Context\Context\CoroutineContext;
use SuperKernel\Context\Context\ProcessContext;
use SuperKernel\Context\Context\ThreadContext;
use SuperKernel\Context\Runtime\EnvironmentContextSelector;

/**
 * Provides static access to values stored in the active execution environment context.
 */
final class Context
{
    /** @var list<class-string> */
    private const array CONTEXTS = [
        CoroutineContext::class,
        ThreadContext::class,
        ProcessContext::class,
    ];

    /**
     * Returns the value stored under the key, or the supplied default when the key is absent.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $context = EnvironmentContextSelector::select(self::CONTEXTS);

        return $context::get($key, $default);
    }

    /**
     * Reports whether the key exists, including when its stored value is null.
     */
    public static function has(string $key): bool
    {
        $context = EnvironmentContextSelector::select(self::CONTEXTS);

        return $context::has($key);
    }

    /**
     * Stores and returns the exact value under the key in the current execution context.
     *
     * @template TValue
     *
     * @param TValue $value
     *
     * @return TValue
     */
    public static function set(string $key, mixed $value): mixed
    {
        $context = EnvironmentContextSelector::select(self::CONTEXTS);

        return $context::set($key, $value);
    }

    /**
     * Clears one key, or all values in the current execution context when no key is supplied.
     */
    public static function clear(?string $key = null): void
    {
        $context = EnvironmentContextSelector::select(self::CONTEXTS);
        $context::clear($key);
    }
}
