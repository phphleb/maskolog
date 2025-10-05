<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Monolog\ResettableInterface;

class ResetSpyProcessor  implements ProcessorInterface, ResettableInterface
{
    private int $numReset = 0;

    public function reset(): void
    {
        $this->numReset++;
    }

    public function getNumReset(): int
    {
        return $this->numReset;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record;
    }
}