<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Enums;

use Psr\Log\LogLevel;

class LoggingLevel
{
    const DEBUG = LogLevel::DEBUG;
    const INFO = LogLevel::INFO;
    const NOTICE = LogLevel::NOTICE;
    const WARNING = LogLevel::WARNING;
    const ERROR = LogLevel::ERROR;
    const CRITICAL = LogLevel::CRITICAL;
    const ALERT = LogLevel::ALERT;
    const EMERGENCY = LogLevel::EMERGENCY;

    /**
     * Returns all logging levels.
     *
     * @return list<'alert'|'critical'|'debug'|'emergency'|'error'|'info'|'notice'|'warning'>
     */
    public static function all(): array
    {
        return [
            self::DEBUG,
            self::INFO,
            self::NOTICE,
            self::WARNING,
            self::ERROR,
            self::CRITICAL,
            self::ALERT,
            self::EMERGENCY,
        ];
    }
}
