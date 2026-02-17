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
    /**
     * @var int
     */
    protected $maxFiles = 0;

    /**
     * @var int
     */
    protected $bufferLimit = 0;

    /**
     * @var string
     */
    protected $maxLevel = LogLevel::ERROR;

    /**
     * @var string
     */
    protected $stream = 'php://stdout';

    /**
     * @var string
     */
    protected $channel = 'app';

    /**
     * @var string
     */
    protected $filePrefix = 'example';

    /**
     * @var bool
     */
    protected $maskingEnabled = true;

    /**
     * Example of setting the parameters required to create a logger.
     */
    public function __construct(
        string $maxLevel = LogLevel::ERROR,
        string $stream = 'php://stdout',
        string $channel = 'app',
        string $filePrefix = 'example',
        bool   $maskingEnabled = true
    )
    {
        $this->maskingEnabled = $maskingEnabled;
        $this->filePrefix = $filePrefix;
        $this->channel = $channel;
        $this->stream = $stream;
        $this->maxLevel = $maxLevel;

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
            ->setTimezone(new DateTimeZone('Europe/Moscow'))
            ->pushProcessor(static function ($record) {
                return (new PsrLogMessageProcessor(null, true))($record);
            });

        if (strpos($this->stream, 'php://') === 0) {
            $handler = new StreamHandler($this->stream, $level);
            $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
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