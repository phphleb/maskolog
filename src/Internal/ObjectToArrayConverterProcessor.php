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
class ObjectToArrayConverterProcessor implements ProcessorInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(#[\SensitiveParameter] LogRecord $record): LogRecord
    {
        $context = $record->context;
        if (!$context) {
            return $record;
        }
        static::update($context);

        return $record->with(context: $context);
    }

    /** @param array<mixed> $context */
    public static function update(#[\SensitiveParameter] array &$context): void
    {
        $converter = static::getConverter();
        array_walk_recursive($context, function (&$item) use ($converter) {
            if (is_object($item)) {
                $item = $converter->convert($item);
            }
        });
    }

    protected static function getConverter(): ObjectToArrayConverter
    {
        return new ObjectToArrayConverter();
    }
}