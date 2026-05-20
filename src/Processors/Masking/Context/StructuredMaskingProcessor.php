<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Processors\Masking\Context;

use Maskolog\Enums\ReplaceMaskingStatus;
use Maskolog\Processors\AbstractContextMaskingProcessor;

/**
 * Context masking that redacts scalar leaf values while preserving structured data shape.
 * Applies to array values, including representations of converted objects.
 * Use when a matched value must stay hierarchical; the flat {@see MaskingProcessor}
 * replaces the entire structure with a single redaction marker.
 */
class StructuredMaskingProcessor extends AbstractContextMaskingProcessor
{
    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function addMask(#[\SensitiveParameter] mixed $value): mixed
    {
        if (!is_array($value)) {
            return ReplaceMaskingStatus::REPLACEMENT->value;
        }

        return array_map($this->addMask(...), $value);
    }
}
