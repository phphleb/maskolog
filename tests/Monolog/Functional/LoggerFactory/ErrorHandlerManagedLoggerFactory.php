<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\LoggerFactory;

use Maskolog\AbstractManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\Source\TestExceptionHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

class ErrorHandlerManagedLoggerFactory extends AbstractManagedLoggerFactory
{
    protected const MAX_LEVEL = LogLevel::DEBUG;

    public const CHANNEL_NAME = 'test.channel';

    public function __construct(
        protected string $maxLevel = self::MAX_LEVEL,
        protected bool   $maskingEnabled = true,
    )
    {
        parent::__construct($maxLevel, $maskingEnabled);
    }

    protected function createMonologLogger(): Logger
    {
        $this->setExceptionHandler(new TestExceptionHandler());

        return (new Logger(self::CHANNEL_NAME))->useMicrosecondTimestamps(true);
    }
}