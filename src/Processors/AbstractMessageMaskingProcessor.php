<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Processors;

use Monolog\LogRecord;

/**
 * The basis for processors that modify only the message based on context.
 */
abstract class AbstractMessageMaskingProcessor implements MaskingProcessorInterface
{
    /**
     * @param LogRecord $record
     * @return LogRecord
     */
    final public function __invoke(#[\SensitiveParameter] LogRecord $record): LogRecord
    {
//        if (is_array($record)) {
//            /** @var array{message: string, context: array<int|string, mixed>} $record */
//            $record['message'] = $this->updateMessage($record['message'], $record['context']);
//
//            return $record;
//        }
        return $record->with(message: $this->updateMessage($record->message, $record->context));
    }

    /**
     * Returns a modified message based on the current message and context.
     * @param array<int|string, mixed> $context
     */
    abstract protected function updateMessage(string $message, array $context): string;
}
