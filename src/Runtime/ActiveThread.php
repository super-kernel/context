<?php

declare(strict_types=1);

namespace SuperKernel\Context\Runtime;

use LogicException;

/**
 * Requires an active Swoole thread and returns its identity for thread-scoped storage.
 *
 * @internal
 */
final class ActiveThread
{
    /**
     * @throws LogicException When no valid Swoole thread is active
     */
    public static function id(): int
    {
        $threadId = ThreadIdentity::detect();

        if (null === $threadId) {
            throw new LogicException('The Swoole thread context requires an active Swoole thread.');
        }

        return $threadId;
    }
}
