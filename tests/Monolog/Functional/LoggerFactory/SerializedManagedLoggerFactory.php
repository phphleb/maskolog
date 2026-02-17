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
    /**
     * @var string
     */
    protected $maxLevel = self::MAX_LEVEL;

    /**
     * @var bool
     */
    protected $maskingEnabled = true;

    public function __construct(
        string $maxLevel = self::MAX_LEVEL,
        bool   $maskingEnabled = true
    )
    {
        $this->maskingEnabled = $maskingEnabled;
        $this->maxLevel = $maxLevel;
        parent::__construct($maxLevel, $maskingEnabled);
    }

    protected function createMonologLogger(): Logger
    {
        return (new Logger(self::CHANNEL_NAME));
    }
}