<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\LoggerFactory;

use Maskolog\AbstractManagedLoggerFactory;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\Source\TestVariableHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LogLevel;

class CombineManagedLoggerFactory extends AbstractManagedLoggerFactory
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

    /**
     * @var TestHandler
     */
    private $unmaskHandler;
    /**
     * @var TestHandler
     */
    private $handler;

    public function __construct(
        TestHandler $handler,
        TestHandler $unmaskHandler,
        string       $maxLevel = self::MAX_LEVEL,
        bool         $maskingEnabled = true
    )
    {
        $this->maskingEnabled = $maskingEnabled;
        $this->maxLevel = $maxLevel;
        parent::__construct($maxLevel, $maskingEnabled);
        $this->unmaskHandler = $unmaskHandler;
        $this->handler = $handler;
    }

    protected function createMonologLogger(): Logger
    {
        $logger = (new Logger(self::CHANNEL_NAME))
            ->useMicrosecondTimestamps(true)
            ->pushProcessor(new PsrLogMessageProcessor(null, false));

          if ($this->maskingEnabled) {
              $this->pushMaskingProcessor( new PasswordMaskingProcessor(['password', 'pass']));
          }

        $logger->pushHandler($this->handler);

        $this->pushUnmaskingHandler($this->unmaskHandler);

        return $logger;
    }
}