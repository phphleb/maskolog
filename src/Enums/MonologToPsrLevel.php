<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Enums;

use Maskolog\Internal\Exceptions\InvalidArgumentException;
use Monolog\Logger;
use Psr\Log\LogLevel;

/**
 * Converts Monolog (RFC 5424) logging levels to PSR-3.
 * Maintains log compatibility across different Monolog versions.
 */
class MonologToPsrLevel {
    const DEBUG = LogLevel::DEBUG;
    const INFO = LogLevel::INFO;
    const NOTICE = LogLevel::NOTICE;
    const WARNING = LogLevel::WARNING;
    const ERROR = LogLevel::ERROR;
    const CRITICAL = LogLevel::CRITICAL;
    const ALERT = LogLevel::ALERT;
    const EMERGENCY = LogLevel::EMERGENCY;

    public static function toPsr3(int $monologLevel): string
    {
        switch ($monologLevel) {
            case Logger::DEBUG:
                return self::DEBUG;
            case Logger::INFO:
                return self::INFO;
            case Logger::NOTICE:
                return self::NOTICE;
            case Logger::WARNING:
                return self::WARNING;
            case Logger::ERROR:
                return self::ERROR;
            case Logger::CRITICAL:
                return self::CRITICAL;
            case Logger::ALERT:
                return self::ALERT;
            case Logger::EMERGENCY:
                return self::EMERGENCY;
            default:
                throw new InvalidArgumentException('Unknown Monolog level: ' . $monologLevel);
        }
    }
}
