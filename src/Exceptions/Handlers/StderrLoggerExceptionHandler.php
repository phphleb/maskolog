<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Exceptions\Handlers;

use DateTime;
use Throwable;

/**
 * Template for redirecting errors that occur during the logger's operation
 * to the standard error stream (stderr).
 */
class StderrLoggerExceptionHandler implements LoggerExceptionHandlerInterface
{
    /**
     * @param Throwable $e
     * @param array{level:string, message:string, context:array<int|string, mixed>} $rawLog
     */
    public function handle(Throwable $e, array $rawLog): void
    {
        $error = (string)json_encode([
            'datetime' => (new DateTime())->format('Y-m-d\TH:i:s.uP'),
            'level' => 'ERROR',
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'log' => [
                'level' => strtoupper($rawLog['level']),
                'message' => $rawLog['message'],
                'context' => $rawLog['context'],
            ]]);

        $stderr = fopen('php://stderr', 'w');
        if ($stderr) {
            fwrite($stderr, $error);
            fclose($stderr);
        }
    }
}