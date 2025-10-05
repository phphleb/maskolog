<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\LoggerFactory;

use Maskolog\AbstractManagedLoggerFactory;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\Source\TestVariableHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LogLevel;

class UnmaskManagedLoggerFactory extends AbstractManagedLoggerFactory
{
    protected const MAX_LEVEL = LogLevel::DEBUG;

    public const CHANNEL_NAME = 'test.channel';
    protected string $maxLevel = self::MAX_LEVEL;
    protected bool $maskingEnabled = true;

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
        $logger = (new Logger(self::CHANNEL_NAME))
            ->useMicrosecondTimestamps(true)
            ->pushProcessor(new PsrLogMessageProcessor(null, false));

          if ($this->maskingEnabled) {
              $this->pushMaskingProcessor( new PasswordMaskingProcessor(['password', 'pass']));
          }

          $this->pushUnmaskingHandler(new TestVariableHandler());

        return $logger;
    }
}