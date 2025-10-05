<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Processors\Masking\Context;

use Maskolog\Enums\StringMaskingStatus;
use Maskolog\Processors\AbstractContextMaskingProcessor;

/**
 * Masking of context data for string.
 * If a processor for substituting context into a message is specified,
 * then it is masked in the message accordingly.
 */
class StringMaskingProcessor extends AbstractContextMaskingProcessor
{
    /**
     * {@inheritDoc}
     *
     * @return mixed
     */
    public function addMask($value)
    {
        if (!$value) {
            return $value;
        }

        if (!is_string($value)) {
            return StringMaskingStatus::detailedFormat($value, StringMaskingStatus::INVALID_TYPE);
        }
        $len = mb_strlen($value);
        $mark = StringMaskingStatus::REPLACEMENT;

        if ($len > 7) {
            return mb_substr($value, 0, 3) . $mark . mb_substr($value, -2);
        }
        if ($len > 4) {
            return mb_substr($value, 0, 1) . $mark . mb_substr($value, -2);
        }

        return $mark;
    }
}
