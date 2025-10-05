<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Maskolog\Logger;
use Monolog\Processor\ProcessorInterface;

class TestExtendedLogger extends Logger
{
    public function withSingleProcessorTest(ProcessorInterface $processor): Logger
    {
        return $this->withSingleProcessorInternal($processor);
    }

    public function withoutProcessorTest(string $class): Logger
    {
      return $this->withoutProcessorInternal($class);
    }
}