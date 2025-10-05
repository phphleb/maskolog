<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Monolog\Level;
use Monolog\LogRecord;

trait CreateLogRecordTrait
{
   protected function createLogRecord(array $data): LogRecord
   {
       return new LogRecord(
           new \DateTimeImmutable(),
           $data['channel'] ?? 'exception',
           $data['level'] ?? Level::Error,
           $data['message'] ?? '',
           $data['context'] ?? [],
           $data['extra'] ?? [],
       );
   }
}