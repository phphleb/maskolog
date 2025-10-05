<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\LoggerFactory;

use Maskolog\AbstractManagedLoggerFactory;
use Monolog\Logger;
use Psr\Log\LogLevel;

class SerializedManagedLoggerFactory extends AbstractManagedLoggerFactory
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
        return (new Logger(self::CHANNEL_NAME));
    }
}