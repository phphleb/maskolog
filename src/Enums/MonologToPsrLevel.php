<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Enums;

use Maskolog\Internal\Exceptions\InvalidArgumentException;
use Monolog\Level;
use Psr\Log\LogLevel;

/**
 * Converts Monolog (RFC 5424) logging levels to PSR-3.
 * Maintains log compatibility across different Monolog versions.
 */
enum MonologToPsrLevel: string
{
    case DEBUG = LogLevel::DEBUG;
    case INFO = LogLevel::INFO;
    case NOTICE = LogLevel::NOTICE;
    case WARNING = LogLevel::WARNING;
    case ERROR = LogLevel::ERROR;
    case CRITICAL = LogLevel::CRITICAL;
    case ALERT = LogLevel::ALERT;
    case EMERGENCY = LogLevel::EMERGENCY;

    public static function toPsr3(int $monologLevel): self
    {
        return match ($monologLevel) {
            Level::Debug->value => self::DEBUG,
            Level::Info->value => self::INFO,
            Level::Notice->value => self::NOTICE,
            Level::Warning->value => self::WARNING,
            Level::Error->value => self::ERROR,
            Level::Critical->value => self::CRITICAL,
            Level::Alert->value => self::ALERT,
            Level::Emergency->value => self::EMERGENCY,
            default => throw new InvalidArgumentException('Unknown Monolog level: ' . $monologLevel),
        };
    }
}
