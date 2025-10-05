<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use JsonException;
use Monolog\Handler\TestHandler;

trait TestHandlerConverterTrait
{
    /**
     * @return object[]
     * @throws JsonException
     */
    protected function convertHandler(TestHandler $handler): array
   {
       $result = [];
       $rows = $handler->getRecords();
       if (!$rows) {
           return $result;
       }

       foreach ($rows as $row) {
           if (is_array($row)) {
               $formatted = $row['formatted'];
           } else {
               $formatted = $row->formatted;
           }
           $result[] = json_decode($formatted, false, 512, JSON_THROW_ON_ERROR);
       }

       return $result;
   }
}