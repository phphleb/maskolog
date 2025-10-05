<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Exceptions\Handlers;

use Throwable;

/**
 * Provides output of errors occurring in the logger itself
 * (in processors, handlers, formatters, etc.) in a different way.
 * This allows avoiding logging loops when intercepting errors for logging.
 */
interface LoggerExceptionHandlerInterface
{
    /**
     * @param Throwable $e
     * @param array{level:string, message:string, context:array<int|string, mixed>} $rawLog
     */
    public function handle(Throwable $e, array $rawLog): void;
}