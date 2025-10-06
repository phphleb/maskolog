<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Processors;

use Monolog\LogRecord;

/**
 * A generic template for masking processor context and message.
 */
abstract class AbstractDataMaskingProcessor implements MaskingProcessorInterface
{
    /**
     * @param LogRecord $record
     * @return LogRecord
     */
    #[\Override]
    final public function __invoke(#[\SensitiveParameter] LogRecord $record): LogRecord
    {
        $message = $record->message;
        $context = $record->context;
        $this->updateData($record->level->toPsrLogLevel(), $message, $context);

        return $record->with(message: $message, context: $context);
    }

    /**
     * Message and context data to change based on level.
     * @param array<int|string, mixed> $context
     */
    abstract protected function updateData(string $level, string &$message, array &$context): void;
}
