<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Processors;

use Maskolog\Enums\MonologToPsrLevel;

/**
 * A generic template for masking processor context and message.
 */
abstract class AbstractDataMaskingProcessor implements MaskingProcessorInterface
{
    final public function __invoke(array $record): array
    {
        /** @var array{level: int, message: string, context: array<int|string, mixed>} $record */
        $this->updateData(
            MonologToPsrLevel::toPsr3($record['level']),
            $record['message'],
            $record['context'],
        );

        return $record;
    }

    /**
     * Message and context data to change based on level.
     * @param array<int|string, mixed> $context
     */
    abstract protected function updateData(string $level, string &$message, array &$context): void;
}
