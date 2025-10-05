<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Internal;

use Monolog\Processor\ProcessorInterface;

/**
 * @internal
 */
class ObjectToArrayConverterProcessor implements ProcessorInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(array $record): array
    {
        $context = $record['context'];
        if (!$context) {
            return $record;
        }
        static::update($context);

        $record['context'] = $context;

        return $record;
    }

    /** @param array<mixed> $context */
    public static function update(array &$context): void
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