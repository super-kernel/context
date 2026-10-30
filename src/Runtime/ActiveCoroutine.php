<?php

declare(strict_types=1);

namespace SuperKernel\Context\Runtime;

use LogicException;
use Swoole\Coroutine;
use Swoole\Coroutine\Context as SwooleCoroutineContext;

use function class_exists;

/**
 * Resolves the native Swoole context of the active coroutine and rejects use outside one.
 *
 * @internal
 */
final class ActiveCoroutine
{
    /**
     * @throws LogicException When the Swoole extension is not loaded or no coroutine is active
     */
    public static function context(): SwooleCoroutineContext
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
}
