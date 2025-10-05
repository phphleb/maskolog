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
     */
    #[\Override]
    public function addMask(#[\SensitiveParameter] mixed $value): mixed
    {
        if (!$value) {
            return $value;
        }

        if (!is_string($value)) {
            return StringMaskingStatus::INVALID_TYPE->detailedFormat($value);
        }
        $len = mb_strlen($value);
        $mark = StringMaskingStatus::REPLACEMENT->value;

        if ($len > 7) {
            return mb_substr($value, 0, 3) . $mark . mb_substr($value, -2);
        } else if ($len > 4) {
            return mb_substr($value, 0, 1) . $mark . mb_substr($value, -2);
        }

        return $mark;
    }
}
