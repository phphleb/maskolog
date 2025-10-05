<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\LoggerFactory;

use MaskologLoggerTests\Monolog\Functional\Source\ResetCloseSpyHandler;
use MaskologLoggerTests\Monolog\Functional\Source\ResetSpyProcessor;
use Monolog\Logger;

class RollbackLoggerFactory extends SimpleTestManagedLoggerFactory
{
    protected function createMonologLogger(): Logger
    {
        $this->pushUnmaskingHandler(new ResetCloseSpyHandler(self::MAX_LEVEL));

        return (new Logger(self::CHANNEL_NAME))
            ->pushProcessor(new ResetSpyProcessor(self::MAX_LEVEL))
            ->useMicrosecondTimestamps(true);
    }
}