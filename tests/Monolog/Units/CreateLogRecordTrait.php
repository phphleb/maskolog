<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Monolog\Level;
use Monolog\Logger;

trait CreateLogRecordTrait
{
   protected function createLogRecord(array $data): array
   {
       return [
           'datetime' => new \DateTimeImmutable(),
           'channel' => $data['channel'] ?? 'exception',
           'level' => $data['level'] ?? Logger::ERROR,
           'message' => $data['message'] ?? '',
           'context' => $data['context'] ?? [],
           'extra' => $data['extra'] ?? [],
           'level_name' => $data['level_name'] ?? 'ERROR',
       ];
   }
}