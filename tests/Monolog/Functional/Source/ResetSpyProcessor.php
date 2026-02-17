<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Monolog\Processor\ProcessorInterface;
use Monolog\ResettableInterface;

class ResetSpyProcessor  implements ProcessorInterface, ResettableInterface
{
    private $numReset = 0;

    public function reset(): void
    {
        $this->numReset++;
    }

    public function getNumReset(): int
    {
        return $this->numReset;
    }

    public function __invoke(array $record): array
    {
        return $record;
    }
}