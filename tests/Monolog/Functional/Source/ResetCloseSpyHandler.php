<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Monolog\Handler\TestHandler;
use Monolog\ResettableInterface;

class ResetCloseSpyHandler extends TestHandler implements ResettableInterface
{
    /**
     * @var int
     */
    private $numClosed = 0;

    /**
     * @var int
     */
    private $numReset = 0;

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