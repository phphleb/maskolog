<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Enums;

use Maskolog\Extensions\ArrayMatchesObjectDefinitionTrait;

/**
 * Contains basic password masking patterns and formatting for the processor.
 * @see PasswordMaskingProcessor
 */
enum PasswordMaskingStatus: string
{
    use ArrayMatchesObjectDefinitionTrait;

    case EMPTY_PASSWORD = '*REDACTED.EMPTY-PASSWORD(%s)*';
    case INVALID_TYPE_PASSWORD = '*REDACTED.INVALID-TYPE-PASSWORD(%s)*';
    case INVALID_LENGTH_PASSWORD = '*REDACTED.INVALID-LENGTH-PASSWORD(%s)*';
    case MASKED_PASSWORD = '*REDACTED.PASSWORD*';

    public function format(#[\SensitiveParameter] mixed $value): string
    {
        return sprintf($this->value, get_debug_type($value));
    }

    /**
     * If object masking is used, it is represented as an array.
     */
    public function detailedFormat(#[\SensitiveParameter] mixed $value): string
    {
        if ($this->matchesObjectShape($value)) {
            /** @var array<string, mixed>  $value */
            return sprintf($this->value, key($value));
        }
        return $this->format($value);
    }

    public function strlen(#[\SensitiveParameter] string $value): string
    {
        return sprintf($this->value, mb_strlen($value));
    }
}
