<?php

declare(strict_types=1);

namespace SuperKernel\Context\Runtime;

use Swoole\Thread;
use Throwable;

use function class_exists;

/**
 * Detects the current Swoole thread identity without failing when thread support is unavailable.
 *
 * @internal
 */
final class ThreadIdentity
{
    /** @return int|null the non-negative Swoole thread ID, or null without a loaded or valid Swoole thread */
    public static function detect(): ?int
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
}
