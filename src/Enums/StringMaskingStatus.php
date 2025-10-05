<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Enums;

use Maskolog\Extensions\ArrayMatchesObjectDefinitionTrait;

/**
 * Contains basic string masking patterns and formatting for the processor.
 * @see StringMaskingProcessor
 */
enum StringMaskingStatus: string
{
    use ArrayMatchesObjectDefinitionTrait;

    case INVALID_TYPE = '*REDACTED.INVALID-TYPE-(%s)*';

    case REPLACEMENT = '*REDACTED*';

    public function format(#[\SensitiveParameter] mixed $value): string
    {
        return sprintf(
            $this->value,
            get_debug_type($value),
        );
    }

    /**
     * If object masking is used, it is represented as an array.
     */
    public function detailedFormat(#[\SensitiveParameter] mixed $value): string
    {
        if ($this->matchesObjectShape($value)) {
            /** @var array<string, mixed>  $value */
            return sprintf(
                $this->value,
                key($value),
            );
        }
        return sprintf(
            $this->value,
            get_debug_type($value),
        );
    }
}
