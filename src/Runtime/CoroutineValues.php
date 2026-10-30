<?php

declare(strict_types=1);

namespace SuperKernel\Context\Runtime;

use Swoole\Coroutine\Context as SwooleCoroutineContext;

use function is_array;

/**
 * Reads the coroutine context's value map from its entry in a native Swoole coroutine context.
 *
 * @internal
 */
final class CoroutineValues
{
    /**
     * @param string $entry native coroutine context entry that holds the value map
     *
     * @return array<string, mixed> the stored map, or an empty map when the entry is absent or not an array
     */
    public static function read(SwooleCoroutineContext $context, string $entry): array
    {
        $values = $context[$entry] ?? [];

        return is_array($values) ? $values : [];
    }
}
