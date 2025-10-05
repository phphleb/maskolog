<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Enums;

use Maskolog\Extensions\ArrayMatchesObjectDefinitionTrait;
use Maskolog\Internal\Functions;

/**
 * Contains basic password masking patterns and formatting for the processor.
 * @see PasswordMaskingProcessor
 */
class PasswordMaskingStatus {

    use ArrayMatchesObjectDefinitionTrait;

    const EMPTY_PASSWORD = '*REDACTED.EMPTY-PASSWORD(%s)*';
    const INVALID_TYPE_PASSWORD = '*REDACTED.INVALID-TYPE-PASSWORD(%s)*';
    const INVALID_LENGTH_PASSWORD = '*REDACTED.INVALID-LENGTH-PASSWORD(%s)*';
    const MASKED_PASSWORD = '*REDACTED.PASSWORD*';

    /**
     * @param mixed $value
     */
    public static function format($value, string $str): string
    {
        return sprintf($str, Functions::getDebugType($value));
    }

    /**
     * If object masking is used, it is represented as an array.
     *
     * @param mixed $value
     */
    public static function detailedFormat($value, string $str): string
    {
        if (self::matchesObjectShape($value)) {
            /** @var array<string, mixed>  $value */
            return sprintf($str, key($value));
        }
        return self::format($value, $str);
    }

    public static function strlen(string $value, string $str): string
    {
        return sprintf($str, mb_strlen($value));
    }
}
