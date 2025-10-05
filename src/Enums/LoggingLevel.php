<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Enums;

use Psr\Log\LogLevel;

enum LoggingLevel: string
{
    case DEBUG = LogLevel::DEBUG;
    case INFO = LogLevel::INFO;
    case NOTICE = LogLevel::NOTICE;
    case WARNING = LogLevel::WARNING;
    case ERROR = LogLevel::ERROR;
    case CRITICAL = LogLevel::CRITICAL;
    case ALERT = LogLevel::ALERT;
    case EMERGENCY = LogLevel::EMERGENCY;

    /**
     * Returns all logging levels.
     *
     * @return list<'alert'|'critical'|'debug'|'emergency'|'error'|'info'|'notice'|'warning'>
     */
    public static function all(): array
    {
        return array_map(fn($level) => $level->value, self::cases());
    }
}
