<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class TestVariableHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        throw new ResultException($record->message);
    }
}