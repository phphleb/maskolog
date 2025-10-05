<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog;

use Maskolog\Internal\Exceptions\InvalidArgumentException;

/**
 * Allows you to define call sources with a specified stack trace level for the logger.
 *
 * When placing the logger in another service or intercepting logs in some framework,
 * a more informative indication of where the initiator is located may be needed.
 * Displaying the location of the initiator is optional and serves to make further work
 * with the logs easier.
 */
final class SourceLocator
{
    /**
     * Get class name, line number, or file name from stack trace at specified level.
     *
     * ```php
     * $logger = $logger->withSource(...SourceLocator::get(1));
     * ```
     *
     * @param int $level - the stack trace level at which to retrieve information.
     * @return array{0: string, 1: int} - contains class name or file name, line number.
     */
    public static function get(int $level = 0): array
    {
        if ($level < 0) {
            throw new InvalidArgumentException('The level can only be a positive number');
        }
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $level + 2);

        $className = '#';
        $lineNumber = 0;
        if (isset($trace[$level], $trace[$level + 1])) {
            $className = $trace[$level + 1]['class'] ?? '#';
            $lineNumber = $trace[$level]['line'] ?? 0;
        }
        if ($className === '#') {
            $className = ($trace[$level]['file'] ?? '#');
        }

        return [$className, $lineNumber];
    }
}

