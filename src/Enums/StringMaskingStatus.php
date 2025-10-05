<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Enums;

use Maskolog\Extensions\ArrayMatchesObjectDefinitionTrait;
use Maskolog\Internal\Functions;

/**
 * Contains basic string masking patterns and formatting for the processor.
 * @see StringMaskingProcessor
 */
class StringMaskingStatus {

    use ArrayMatchesObjectDefinitionTrait;

    const INVALID_TYPE = '*REDACTED.INVALID-TYPE-(%s)*';

    const REPLACEMENT = '*REDACTED*';

    /**
     * @param mixed $value
     */
    public static function format($value, string $str): string
    {
        return sprintf(
            $str,
            Functions::getDebugType($value),
        );
    }

    /**
     * If object masking is used, it is represented as an array.
     *
     * @param mixed $value
     */
    public static function detailedFormat($value, string $str): string
    {
        if (self::matchesObjectShape($value)) {
            /** @var array<string, mixed> $value */
            return sprintf(
                $str,
                key($value),
            );
        }
        return sprintf(
            $str,
            Functions::getDebugType($value),
        );
    }
}
