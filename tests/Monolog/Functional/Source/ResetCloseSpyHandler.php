<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Monolog\Handler\TestHandler;
use Monolog\ResettableInterface;

class ResetCloseSpyHandler extends TestHandler implements ResettableInterface
{
    private int $numClosed = 0;

    private int $numReset = 0;

    public function close(): void
    {
        $this->numClosed++;
    }

    public function reset(): void
    {
        $this->numReset++;
    }

    public function getNumClosed(): int
    {
        return $this->numClosed;
    }

    public function getNumReset(): int
    {
        return $this->numReset;
    }
}