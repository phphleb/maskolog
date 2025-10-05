<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Internal;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * @internal
 */
class ExtraModifierProcessor implements ProcessorInterface
{
    /**
     * @param array<int|string, mixed> $extra
     */
    public function __construct(#[\SensitiveParameter] private readonly array $extra)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(#[\SensitiveParameter] LogRecord $record): LogRecord
    {
        return $record->with(extra: array_merge($record->extra, $this->extra));
    }
}