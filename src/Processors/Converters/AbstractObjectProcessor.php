<?php

declare(strict_types=1);

namespace Maskolog\Processors\Converters;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * @see AbstractManagedLoggerFactory::pushUpdateObjectProcessor()
 */
abstract class AbstractObjectProcessor implements ProcessorInterface
{
   final public function __invoke(LogRecord $record): LogRecord
   {
        $context = $record->context;
        if (!$context) {
            return $record;
        }
       /** @var array<string, array<mixed>|string|int|null> $context */
       $context = $this->update($context);

        return $record->with(context: $context);
    }

    /**
     * @param array<mixed> $context
     * @return array<mixed>
     */
   final public function update(array $context): array
   {
       $walker = function (&$value) use (&$walker) {
           if (is_array($value)) {
               foreach ($value as &$v) {
                   $walker($v);
               }
               unset($v);
               return;
           }

           if (is_object($value)) {
               $value = $this->updateObject($value);
           }
       };

       foreach ($context as &$item) {
           $walker($item);
       }
       unset($item);

       return $context;
   }

    /**
     * @return array<mixed>|object
     */
    abstract public function updateObject(object $object): array|object;
}