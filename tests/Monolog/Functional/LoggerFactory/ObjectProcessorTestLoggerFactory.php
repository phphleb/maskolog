<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\LoggerFactory;

use Maskolog\AbstractManagedLoggerFactory;
use Maskolog\Processors\Converters\Psr7RequestProcessor;
use Maskolog\Processors\Converters\Psr7ResponseProcessor;
use Monolog\Logger;
use Psr\Log\LogLevel;

class ObjectProcessorTestLoggerFactory extends AbstractManagedLoggerFactory
{
    protected const MAX_LEVEL = LogLevel::DEBUG;

    public const CHANNEL_NAME = 'test.channel';

    public function __construct(
        protected string $maxLevel = self::MAX_LEVEL,
        protected bool   $maskingEnabled = true,
        protected bool   $maskObjects = true,
    )
    {
        parent::__construct($maxLevel, $maskingEnabled, maskObjects: $maskObjects);
    }

    protected function createMonologLogger(): Logger
    {
        $this->pushUpdateObjectProcessor(new Psr7RequestProcessor());
        $this->pushUpdateObjectProcessor(new Psr7ResponseProcessor());

        return (new Logger(self::CHANNEL_NAME))->useMicrosecondTimestamps(true);
    }
}