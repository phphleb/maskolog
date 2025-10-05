<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Processors\Masking\Context;

use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Processors\AbstractContextMaskingProcessor;

/**
 * Masking of context data for passwords.
 * If a processor for substituting context into a message is specified,
 * then it is masked in the message accordingly.
 */
class PasswordMaskingProcessor extends AbstractContextMaskingProcessor
{
    public const MIN_PASSWORD_LENGTH = 8;

    /**
     * {@inheritDoc}
     */
    public function addMask(#[\SensitiveParameter] mixed $value): string
    {
        if (!$value) {
            return PasswordMaskingStatus::EMPTY_PASSWORD->format($value);
        }
        if (!is_string($value)) {
            return PasswordMaskingStatus::INVALID_TYPE_PASSWORD->detailedFormat($value);
        }

        if (mb_strlen($value) < self::MIN_PASSWORD_LENGTH) {
            return PasswordMaskingStatus::INVALID_LENGTH_PASSWORD->strlen($value);
        }

        return PasswordMaskingStatus::MASKED_PASSWORD->value;
    }
}
