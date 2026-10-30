<?php

declare(strict_types=1);

namespace SuperKernel\Context\Runtime;

use LogicException;

/**
 * Selects the first environment context, in the supplied priority order, that supports the current environment.
 *
 * @internal
 */
final class EnvironmentContextSelector
{
    /**
     * @param list<class-string> $contexts Environment context classes in priority order
     *
     * @return class-string
     *
     * @throws LogicException when no configured context supports the current environment
     */
    public static function select(array $contexts): string
    {
        foreach ($contexts as $context) {
            if ($context::support()) {
                return $context;
            }
        }

        throw new LogicException('No context supports the current execution environment.');
    }
}
