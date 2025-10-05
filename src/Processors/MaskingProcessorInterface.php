<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Processors;

use Monolog\LogRecord;

/**
 * Common interface for masking processors.
 */
interface MaskingProcessorInterface
{
    /**
     * For different versions of Monolog, a composite data type is specified.
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    public function __invoke(array $record): array;
}