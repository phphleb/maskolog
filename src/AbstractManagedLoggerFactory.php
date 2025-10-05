<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog;

use Maskolog\Enums\LoggingLevel;
use Maskolog\Exceptions\Handlers\LoggerExceptionHandlerInterface;
use Maskolog\Internal\Exceptions\InvalidArgumentException;
use Maskolog\Internal\Exceptions\LogicException;
use Maskolog\Internal\SerializableLoggerMap;
use Maskolog\Processors\Converters\AbstractObjectProcessor;
use Maskolog\Processors\MaskingProcessorInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger as MonologLogger;

/**
 * Creates a logger on demand along with resources
 * that are global in the context of that logger.
 */
abstract class AbstractManagedLoggerFactory
{
    private ?MonologLogger $logger = null;

    /**
     * @var MaskingProcessorInterface[]
     */
    private array $maskingProcessors = [];

    /**
     * @var HandlerInterface[]
     */
    private array $unmaskingHandlers = [];

    /** @var AbstractObjectProcessor[] */
    private array $updateObjectProcessors = [];

    private ?LoggerExceptionHandlerInterface $exceptionHandler = null;


    private SerializableLoggerMap $weakLoggerMap;

    /**
     * Basic mandatory logger constructor.
     *
     * @param string $maxLevel - sets the allowed logging level according to PSR-3.
     * @param bool $maskingEnabled - enable masking mode.
     * @param bool $deduplicateMaskingProcessors - optimize masking processors and remove duplicates.
     * @param bool $maskObjects - mask all objects by attributes in context.
     */
    public function __construct(
        protected string $maxLevel,
        protected bool   $maskingEnabled,
        protected bool   $deduplicateMaskingProcessors = true,
        protected bool   $maskObjects = true,
    )
    {
        if (!in_array($maxLevel, LoggingLevel::all())) {
            throw new InvalidArgumentException('The specified logging level was not found: ' . $maxLevel);
        }
        $this->weakLoggerMap = new SerializableLoggerMap();
    }

    /**
     * @return MaskingProcessorInterface[]
     */
    public function getMaskingProcessors(): array
    {
        return $this->maskingProcessors;
    }

    /**
     * @return AbstractObjectProcessor[]
     * @internal
     */
    public function getUpdateObjectProcessors(): array
    {
        return $this->updateObjectProcessors;
    }

    /**
     * @internal
     * @return HandlerInterface[]
     */
    public function getUnmaskingHandlers(): array
    {
        return $this->unmaskingHandlers;
    }

    /**
     * @internal
     */
    final public function getLogger(): MonologLogger
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4) as $i => $t) {
            if ($i === 0) continue;
            if (!empty($t['class']) && is_subclass_of($t['class'], self::class)) {
                throw new LogicException('Call of ' . __METHOD__ . ' from inherited class is prohibited');
            }
        }

        return $this->logger ?? $this->logger = $this->createMonologLogger();
    }

    /**
     * Returns the maximum PSR-3 compliant.
     *
     * @return ('alert'|'critical'|'debug'|'emergency'|'error'|'info'|'notice'|'warning')
     */
    public function getMaxLevel(): string
    {
        /** @var ('alert'|'critical'|'debug'|'emergency'|'error'|'info'|'notice'|'warning') */
        return $this->maxLevel;
    }

    /**
     * Returns the activity status for masking.
     */
    final public function maskingEnabled(): bool
    {
        return $this->maskingEnabled;
    }

    /**
     * Returns the status of removing duplicate masking processors
     * with merging parameters.
     */
    final public function shouldRemoveMaskingDuplicates(): bool
    {
        return $this->deduplicateMaskingProcessors;
    }

    /**
     * @internal
     *
     * Returns the default other error handler if one has been added.
     */
    final public function getExceptionHandler(): ?LoggerExceptionHandlerInterface
    {
        return $this->exceptionHandler;
    }

    /**
     * @internal
     * Called when the method of the same name is called
     * on the main logger instance.
     *
     * @see MonologLogger::reset()
     */
    public function reset(): void
    {
        foreach ($this->weakLoggerMap->all() as $link => $v) {
            $link->reset();
        }
    }

    /**
     * @internal
     * Called when the method of the same name is called
     * on the main logger instance.
     *
     * @see MonologLogger::close()
     */
    final public function close(): void
    {
        foreach ($this->weakLoggerMap->all() as $link => $v) {
            $link->close();
        }
    }

    /**
     * @internal
     * Uses only the current logger instance.
     *
     * @see MonologLogger::reset()
     */
    public function resetOnce(): void
    {
        $this->logger?->reset();
    }

    /**
     * @internal
     * Uses only the current logger instance.
     *
     * @see MonologLogger::close()
     */
    final public function closeOnce(): void
    {
        $this->logger?->close();
    }

    /**
     * @internal
     * Adding a weak reference to the Monolog logger instance.
     */
    final public function registerWeakReference(MonologLogger $instance): void
    {
        $this->weakLoggerMap->add($instance);
    }

    /**
     * Enables masking of objects by first converting them to an array.
     */
    public function maskObjects(): bool
    {
        return $this->maskObjects;
    }

    /**
     * Adds a masking processor globally in the context of this logger.
     */
    final protected function pushMaskingProcessor(MaskingProcessorInterface $processor): void
    {
        if ($this->maskingEnabled) {
            array_unshift($this->maskingProcessors, $processor);
        }
    }

    /**
     * Adding a handler that does not mask even if masking is active.
     * This can be useful when outputting raw logs to a more private log storage
     * than the main ones that will be masked.
     */
    final protected function pushUnmaskingHandler(HandlerInterface $handler): void
    {
        array_unshift($this->unmaskingHandlers, $handler);
    }


    /**
     * Forces globally queued processors for priority execution in the masking logger.
     * These processors will be executed first before the processor that converts objects to arrays,
     * i.e., they allow specific objects to be pre-processed before conversion.
     * ```php
     *  use Maskolog\Processors\Converters\{PsrRequestProcessor,PsrResponseProcessor};
     *
     *  $this->pushUpdateObjectProcessor(new Psr7RequestProcessor());
     *  $this->pushUpdateObjectProcessor(new Psr7ResponseProcessor());
     * ```
     */
    final protected function pushUpdateObjectProcessor(AbstractObjectProcessor $processor): void
    {
        array_unshift($this->updateObjectProcessors, $processor);
    }

    /**
     * Sets another error handler when errors occur in the logger itself
     * (in processors, handlers, formatters, etc.) to prevent logs
     * from looping when outputting errors (if implemented by the application).
     */
    final protected function setExceptionHandler(LoggerExceptionHandlerInterface $handler): void
    {
        $this->exceptionHandler = $handler;
    }

     /**
     * Returns a constructed logger based on user-defined conditions
     * for setting the original Monolog logger in the context
     * of the current logger instance.
     * Requested once for each logger instance.
     *
     * @see ExampleManagedLoggerFactory
     */
    abstract protected function createMonologLogger(): MonologLogger;
}