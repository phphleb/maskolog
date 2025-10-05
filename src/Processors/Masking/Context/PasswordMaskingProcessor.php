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
    public function addMask($value): string
    {
        if (!$value) {
            return PasswordMaskingStatus::format($value, PasswordMaskingStatus::EMPTY_PASSWORD);
        }
        if (!is_string($value)) {
            return PasswordMaskingStatus::detailedFormat($value, PasswordMaskingStatus::INVALID_TYPE_PASSWORD);
        }

        if (mb_strlen($value) < self::MIN_PASSWORD_LENGTH) {
            return PasswordMaskingStatus::strlen($value, PasswordMaskingStatus::INVALID_LENGTH_PASSWORD);
        }

        return PasswordMaskingStatus::MASKED_PASSWORD;
    }
}
