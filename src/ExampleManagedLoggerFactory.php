<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog;

use DateTimeZone;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LogLevel;

 /**
 * Example for building a custom logger with Monolog initialization.
 * Responsible for global logging configuration and can be initialized in any desired way.
 */
class ExampleManagedLoggerFactory extends AbstractManagedLoggerFactory
{
    protected int $maxFiles = 0;

    protected int $bufferLimit = 0;

    /**
     * Example of setting the parameters required to create a logger.
     */
    public function __construct(
        protected string $maxLevel = LogLevel::ERROR,
        protected string $stream = 'php://stdout',
        protected string $channel = 'app',
        protected string $filePrefix = 'example',
        protected bool   $maskingEnabled = true,
    )
    {
        parent::__construct($maxLevel, $maskingEnabled);
    }

    /**
     * @inheritDoc
     *
     * Lazy logger loading.
     * Called when it is necessary to create the original Monolog logger instance.
     */
    protected function createMonologLogger(): Logger
    {
        $level = $this->getMaxLevel();

        $logger = (new Logger($this->channel))
            ->useMicrosecondTimestamps(true)
            ->setTimezone(new DateTimeZone('UTC'))
            ->pushProcessor(static function ($record) {
                return (new PsrLogMessageProcessor(removeUsedContextFields: true))($record);
            });

        if (str_starts_with($this->stream, 'php://')) {
            $handler = new StreamHandler($this->stream, $level);
            $handler->setFormatter(new JsonFormatter(includeStacktraces: true));
            $logger->pushHandler(new BufferHandler($handler, $this->bufferLimit, $level));
        } else {
            $stream = rtrim($this->stream, '/\\') . DIRECTORY_SEPARATOR;
            $handler = new RotatingFileHandler("{$stream}{$this->filePrefix}.log", $this->maxFiles, $level, true, 0777);
            $handler->setFormatter(new LineFormatter());
            $logger->pushHandler($handler);
        }

        //  $this->pushMaskingProcessor(/** Initial masking processor */);
        //  $this->pushUnmaskingHandler(/* Initial handler without masking */);
        //  $this->setExceptionHandler(new StderrLoggerExceptionHandler());
        //

        return $logger;
    }
 }