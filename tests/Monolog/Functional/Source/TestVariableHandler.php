<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Monolog\Handler\AbstractProcessingHandler;

class TestVariableHandler extends AbstractProcessingHandler
{
    protected function write(array $record): void
    {
        throw new ResultException($record['message']);
    }
}