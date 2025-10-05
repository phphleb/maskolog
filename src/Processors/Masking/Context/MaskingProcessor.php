<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Processors\Masking\Context;

use Maskolog\Enums\ReplaceMaskingStatus;
use Maskolog\Processors\AbstractContextMaskingProcessor;

class MaskingProcessor extends AbstractContextMaskingProcessor
{
    /**
     * {@inheritDoc}
     *
     * @param mixed $value
     */
    #[\Override]
    public function addMask(#[\SensitiveParameter] mixed $value): string
    {
        return ReplaceMaskingStatus::REPLACEMENT->value;
    }
}