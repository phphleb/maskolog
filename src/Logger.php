<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog;

use Maskolog\Enums\LoggingLevel;
use Maskolog\Enums\MonologToPsrLevel;
use Maskolog\Exceptions\Handlers\LoggerExceptionHandlerInterface;
use Maskolog\Exceptions\MaskingExceptionInterface;
use Maskolog\Internal\Exceptions\InvalidArgumentException;
use Maskolog\Internal\Exceptions\LogicException;
use Maskolog\Internal\ExtraModifierProcessor;
use Maskolog\Internal\ObjectToArrayConverterProcessor;
use Maskolog\Internal\ObjectToMaskedArrayConverterProcessor;
use Maskolog\Internal\ProcessorManager;
use Maskolog\Processors\MaskingProcessorInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;

/**
 * Wrapper over Monolog to mask logs.
 */
class Logger implements LoggerInterface
{
    /**
     * @var array<int, MaskingProcessorInterface|array<int|string, mixed>|callable>
     */
    private array $maskingProcessors = [];

    /**
     * @var array<int, callable|ProcessorInterface|null>
     */
    private array $processors = [];

    /**
     * @var HandlerInterface[]
     */
    private array $unmaskingHandlers = [];

    /**
     * @var HandlerInterface[]
     */
    private array $handlers = [];

    private ?MonologLogger $maskingLogger = null;

    private ?MonologLogger $unmaskingLogger = null;

    private string $maxLevel;

    private bool $isEnableMasking;

    private bool $removeDuplicates;

    private string $name = '';

    private ?LoggerExceptionHandlerInterface $exceptionHandler;

    public function __construct(readonly private AbstractManagedLoggerFactory $loggerFactory)
    {
        /** If the values were added in the factory constructor. */
        $this->exceptionHandler = $loggerFactory->getExceptionHandler();

        $this->maxLevel = $loggerFactory->getMaxLevel();
        $this->isEnableMasking = $loggerFactory->maskingEnabled();
        $this->removeDuplicates = $loggerFactory->shouldRemoveMaskingDuplicates();
    }

    /**
     * @inheritDoc
     */
    public function emergency(
        #[\SensitiveParameter] string|Stringable $message,
        #[\SensitiveParameter] array             $context = [],
    ): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function alert(
        #[\SensitiveParameter] string|Stringable $message,
        #[\SensitiveParameter] array             $context = [],
    ): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function critical(
        #[\SensitiveParameter] string|Stringable $message,
        #[\SensitiveParameter] array             $context = [],
    ): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function error(
        #[\SensitiveParameter] string|Stringable $message,
        #[\SensitiveParameter] array             $context = [],
    ): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function warning(
        #[\SensitiveParameter] string|Stringable $message,
        #[\SensitiveParameter] array             $context = [],
    ): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function notice(
        #[\SensitiveParameter] string|Stringable $message,
        #[\SensitiveParameter] array             $context = [],
    ): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function info(
        #[\SensitiveParameter] string|Stringable $message,
        #[\SensitiveParameter] array             $context = [],
    ): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function debug(
        #[\SensitiveParameter] string|Stringable $message,
        #[\SensitiveParameter] array             $context = [],
    ): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Sends logs with PSR-3.
     *
     * @param int|Stringable|string|Level $level - PSR-3 or Monolog level.
     * @param array<mixed> $context
     */
    public function log(
        $level,
        #[\SensitiveParameter] string|Stringable $message,
        #[\SensitiveParameter] array $context = [],
    ): void
    {
        $level = $this->convertLevel($level);
        if (!in_array($level, LoggingLevel::all())) {
            throw new InvalidArgumentException('The specified logging level was not found: ' . $level);
        }
        if (!$this->isLevelWithinLimit($level)) {
            return;
        }
        $message = (string)$message;
        try {
            $this->send($level, $message, $context);
        } catch (Throwable $t) {
            $this->handleLoggerException($t, compact('level', 'message', 'context'));
        }
    }

    /**
     * Returns a clone of the logger with the masking processor assigned.
     * The processor can be an object with the MaskingProcessorInterface interface or an executable entity.
     * Masking is applied only if the active masking mode is passed in the logger factory.
     *
     * ```php
     * $logger = $logger->withMaskingProcessor(
     *           new PasswordMaskingProcessor(['password'])
     *      );
     * // or
     * $logger = $logger->withMaskingProcessor(function($record) {
     *           return new PasswordMaskingProcessor(['password'])($record);
     *      );
     * ```
     */
    final public function withMaskingProcessor(MaskingProcessorInterface|callable $value): static
    {
        return $this->withMaskingProcessors([$value]);
    }

    /**
     * Returns a clone of the current logger with masking processors added.
     * The assigned values can be objects with the MaskingProcessorInterface interface,
     * executable entities, or array values where the key is the name of the masking class
     * with the value as constructor parameters.
     * Masking is applied only if the active masking mode is passed in the logger factory.
     *
     * ```php
     *   $logger = $logger->withMaskingProcessors([
     *            // masking processor object.
     *            new UrlMaskingProcessor(['url']),
     *
     *            // anonymous function.
     *            function($record) {
     *                return (new StringMaskingProcessor(['token', 'hash']))($record);
     *            },
     *
     *            // a lightweight version of adding a masking processor.
     *            PasswordMaskingProcessor::class => ['password', 'passwd']
     *       ]);
     *
     * ```
     *
     * @param array<int|string, array<int|string, mixed>|string|MaskingProcessorInterface|callable> $value
     */
    final public function withMaskingProcessors(array $value): static
    {
        ProcessorManager::check($value);
        $new = clone $this;
        if ($this->isEnableMasking) {
            foreach ($value as $key => &$processor) {
                if (is_string($key)) {
                    if (is_string($processor)) {
                        $processor = [$processor];
                    }
                    if (is_array($processor)) {
                        $processor = [$key => $processor];
                        if ($this->removeDuplicates) {
                            $updMaskingProcessors = ProcessorManager::removeDuplicates(
                                $new->maskingProcessors,
                                $key,
                                current($processor),
                            );
                            if ($updMaskingProcessors) {
                                $new->maskingProcessors = $updMaskingProcessors;
                                continue;
                            }
                        }
                    }
                }
                /** @var array<int|string, mixed> $processor */
                array_unshift($new->maskingProcessors, $processor);
                // The order of adding the masking element is fixed.
                array_unshift($new->processors, null);
            }
            $new->maskingLogger = null;
        }
        return $new;
    }

    /**
     * Returns a clone of the current logger with the Monolog processor added.
     */
    final public function withProcessor(ProcessorInterface|callable $callback): static
    {
        $new = clone $this;
        array_unshift($new->processors, $callback);
        if (current($new->processors)) {
            if ($new->unmaskingLogger) {
                $new->unmaskingLogger = (clone $new->unmaskingLogger)->pushProcessor(current($new->processors));
                $this->loggerFactory->registerWeakReference($new->unmaskingLogger);
            }
            if ($new->maskingLogger) {
                $new->maskingLogger = (clone $new->maskingLogger)->pushProcessor(current($new->processors));
                $this->loggerFactory->registerWeakReference($new->maskingLogger);
            }
        }

        return $new;
    }

    /**
     * Returns a clone of the current logger with a Monolog handler added,
     * this handler uses unmasked data even if masking is active.
     * When logging methods are called, this handler will be executed
     * along with other handlers.
     * To get a separate logger with only unmasked handlers,
     * use the getMonologUnmaskingLogger method.
     *
     * @see self::getMonologUnmaskingLogger()
     */
    final public function withUnmaskingHandler(HandlerInterface $handler): static
    {
        $new = clone $this;
        array_unshift($new->unmaskingHandlers, $handler);
        if ($new->unmaskingLogger) {
            $new->unmaskingLogger = (clone $new->unmaskingLogger)->pushHandler(current($new->unmaskingHandlers));
            $this->loggerFactory->registerWeakReference($new->unmaskingLogger);
        }

        return $new;
    }

    /**
     * Returns a clone of the logger with the Monolog handler added.
     */
    final public function withHandler(HandlerInterface $handler): static
    {
        $new = clone $this;
        array_unshift($new->handlers, $handler);
        if ($new->maskingLogger) {
            $new->maskingLogger = (clone $new->maskingLogger)->pushHandler(current($new->handlers));
            $this->loggerFactory->registerWeakReference($new->maskingLogger);
        }

        return $new;
    }

    /**
     * Sets the file/class name and line number in the log.
     * This can be useful if you are using a logger inside a wrapper
     * and need to track the location of logger calls in higher-level code.
     *
     * ```php
     *  $logger = $logger->withSource(...SourceLocator::get($traceLevel));
     *  ```
     */
    final public function withSource(string $file, int $line): static
    {
        return $this->withSingleProcessorInternal(new ExtraModifierProcessor(['source' => $file . ($line ? ':' . $line : '')]));
    }

    /**
     * Returns a clone of the logger with an added separate error handler
     * that occurs in the logger itself.
     * Such a handler will avoid log looping if application
     * (and logger) errors are sent to the logger.
     */
    final public function withExceptionHandler(?LoggerExceptionHandlerInterface $handler): static
    {
        $new = clone $this;
        $new->exceptionHandler = $handler;

        return $new;
    }

    /**
     * Returns a separate handler for errors inside the logger, if one was added earlier.
     */
    final public function getExceptionHandler(): ?LoggerExceptionHandlerInterface
    {
        return $this->hasExceptionHandler() ? $this->exceptionHandler : null;
    }

    /**
     * Returns the result of checking for the existence of the added separate error handler.
     *
     * @see self::getExceptionHandler()
     */
    final public function hasExceptionHandler(): bool
    {
        if ($this->exceptionHandler) {
            return true;
        }
        $this->getMonologMaskingLogger();

        return !is_null($this->loggerFactory->getExceptionHandler());
    }

    /**
     * Returns an exception object with masking processors from the logger added.
     */
    final public function createMaskedException(
        MaskingExceptionInterface $e,
    ): MaskingExceptionInterface
    {
        foreach(array_reverse($this->getMaskingProcessors()) as $processor) {
            $e->pushMaskingProcessor($processor);
        }
        return $e->finalize($this->isEnableMasking);
    }

    /**
     * Throws an exception using masking.
     *
     * @throws MaskingExceptionInterface
     */
    final public function throwMaskedException(
        MaskingExceptionInterface $e,
    ): void
    {
        throw $this->createMaskedException($e);
    }

    final public function isEnableMasking(): bool
    {
        return $this->isEnableMasking;
    }

    /**
     * Returns only the logger's masking processors.
     *
     * @return (callable|MaskingProcessorInterface)[]
     */
    final public function getMaskingProcessors(): array
    {
        if (!$this->isEnableMasking) {
            return $this->loggerFactory->maskObjects() ? [new ObjectToArrayConverterProcessor()] : [];
        }
        $this->getMaskingLogger();
        /**
         * @var (callable|MaskingProcessorInterface)[]
         */
        return array_merge(
            $this->loggerFactory->maskObjects() ? [new ObjectToMaskedArrayConverterProcessor()] : [],
            ProcessorManager::convert($this->maskingProcessors),
            $this->loggerFactory->getMaskingProcessors(),
        );
    }

    /**
     * Returns the result of checking whether the Logger has a handler
     * that listens at the specified level.
     * Also checks against the overall logging level set.
     */
    final public function isHandling(int|string|Level $level): bool
    {
        $level = $this->convertLevel($level);
        if (!in_array($level, LoggingLevel::all())) {
            throw new InvalidArgumentException('The specified logging level was not found: ' . $level);
        }
        if (!$this->isLevelWithinLimit($level)) {
            return false;
        }
        if ($this->handlers || $this->hasUnmaskingHandlers()) {
            $record = new LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: $this->name,
                level: MonologLogger::toMonologLevel($level),
                message: '',
            );
            foreach ($this->handlers as $handler) {
                if ($handler->isHandling($record)) {
                    return true;
                }
            }
            foreach ($this->getUnmaskingHandlers() as $handler) {
                if ($handler->isHandling($record)) {
                    return true;
                }
            }
        }
        return $this->getMonologMaskingLogger()->isHandling($level)
            || $this->getMonologUnmaskingLogger()?->isHandling($level);
    }

    /**
     * Return a new cloned instance with the name (channel) changed.
     */
    final public function withName(string $name): static
    {
        $new = clone $this;
        $new->name = $name;
        $new->maskingLogger = $new->maskingLogger?->withName($name);
        $new->unmaskingLogger = $new->unmaskingLogger?->withName($name);

        return $new;
    }

    /**
     * When called, it works on all instances of Monolog loggers
     * derived from the original masked logger.
     *
     * Used in the context of long-running processes
     * to prevent state accumulation inside persistent workers.
     *
     * Example of a service for Symfony:
     * ```php
     * class ResettableLoggerService extends Logger implements ResetInterface
     * {
     * }
     * ```
     * @see \Monolog\Logger::reset()
     */
    final public function reset(): void
    {
        $this->loggerFactory->reset();
    }

    /**
     * When called, only the current instance is used.
     * This will also apply to all referenced resources
     * received by this instance from previous ones.
     *
     * Presumably, you might need this method
     * when preparing this instance for the next round of logging.
     *
     * @see \Monolog\Logger::reset()
     */
    final public function resetCurrent(): void
    {
        $this->maskingLogger?->reset();
        $this->unmaskingLogger?->reset();
    }

    /**
     * When called, it works on all instances of Monolog loggers
     * derived from the original masked logger.
     *
     * @see \Monolog\Logger::close()
     */
    public function close(): void
    {
        $this->loggerFactory->close();
    }

    /**
     * When called, only the current instance is used.
     * This will also apply to all referenced resources
     * received by this instance from previous ones.
     *
     * @see \Monolog\Logger::close()
     */
    final public function closeCurrent(): void
    {
        $this->maskingLogger?->close();
        $this->unmaskingLogger?->close();
    }

    /**
     * Returns the current Monolog logger with all processors
     * and handlers except handlers not involved in masking.
     */
    final public function getMaskingLogger(): MonologLogger
    {
        $logger = clone $this->getMonologMaskingLogger();
        $this->loggerFactory->registerWeakReference($logger);

        return $logger;
    }

    /**
     * Checks if it is possible to get a logger with only handlers
     * that do not participate in masking.
     * The presence of such a logger depends on the existence
     * of non-masking handlers.
     */
    final public function hasUnmaskingLogger(): bool
    {
        return $this->unmaskingLogger || $this->hasUnmaskingHandlers();
    }

    /**
     * Returns a Monolog logger with only handlers that do not participate in masking.
     * It will also lack masking processors.
     * The presence of such a logger depends on the existence
     * of non-masking handlers.
     */
    final public function getUnmaskingLogger(): ?MonologLogger
    {
        $logger = $this->getMonologUnmaskingLogger();
        if ($logger) {
            $logger = clone $logger;
            $this->loggerFactory->registerWeakReference($logger);
        }

       return $logger;
    }

    /**
     * @internal - For replacement of original Monolog object only.
     *
     * @see MonologLogger::pushProcessor()
     */
    public function pushProcessor(ProcessorInterface|callable $callback): self
    {
        array_unshift($this->processors, $callback);

        return $this;
    }

    /**
     * @internal - For replacement of original Monolog object only.
     *
     * @see MonologLogger::pushHandler()
     */
    public function pushHandler(HandlerInterface $handler): self
    {
        array_unshift($this->handlers, $handler);

        return $this;
    }

    /**
     * Returns a clone of the current logger with a new processor
     * and with tracking to make sure that this processor is unique.
     * All objects of this class previously added will be removed
     * from the processors.
     * The replacement rule only applies to processors added as objects.
     */
    final protected function withSingleProcessorInternal(ProcessorInterface $processor): static
    {
        $new = $this->withoutProcessorInternal(get_class($processor));

        return $new->withProcessor($processor);
    }

    /**
     * Returns a clone of the current logger from whose processors
     * the specified one by class or parent class has been removed.
     * The action does not apply to masking processors.
     * Can be used on standard Monolog processors added as objects.
     */
    final protected function withoutProcessorInternal(string $class): static
    {
        if (is_a($class, MaskingProcessorInterface::class, true)) {
            throw new LogicException('Not supported for masking processors');
        }
        $new = clone $this;
        $new->maskingLogger = null;
        $new->unmaskingLogger = null;
        foreach($new->processors as $k => $p) {
            if ($p instanceof $class) {
                unset($new->processors[$k]);
            }
        }
        $new->processors = array_values($new->processors);

        return $new;
    }

     /**
     * @param Throwable $t
     * @param array{level:string, message:string, context:array<int|string, mixed>} $rawLog
     */
    private function handleLoggerException(Throwable $t, array $rawLog): void
    {
        if ($this->getExceptionHandler()) {
            $this->exceptionHandler?->handle($t, $rawLog);
            return;
        }
        throw $t;
    }

    /**
     * @param array<mixed> $context
     */
    private function send(string $level, string $message, array $context): void
    {
        $reachedMainLogging = false;
        /**
         * @var array<mixed> $context
         * @var 'alert'|'critical'|'debug'|'emergency'|'error'|'info'|'notice'|'warning' $level
         */
        try {
            $logger = $this->getMonologMaskingLogger();
            if ($context) {
                $this->updateContext($context);
            }
            $reachedMainLogging = true;
            $logger->log($level, $message, $context);
        } finally {
            $unmaskingLogger = $this->getMonologUnmaskingLogger();
            if ($unmaskingLogger && !$reachedMainLogging && $context) {
                $this->updateContext($context);
            }
            $unmaskingLogger?->log($level, $message, $context);
        }
    }

    /**
     * @param array<mixed> $context
     * @return void
     */
    private function updateContext(array &$context): void
    {
        foreach ($this->loggerFactory->getUpdateObjectProcessors() as $firstProcessors) {
            $context = $firstProcessors->update($context);
        }
        if ($this->loggerFactory->maskObjects()) {
            if ($this->isEnableMasking) {
                ObjectToMaskedArrayConverterProcessor::update($context);
            } else {
                ObjectToArrayConverterProcessor::update($context);
            }
        }
    }

    private function getMonologMaskingLogger(): MonologLogger
    {
        if ($this->maskingLogger) {
            return $this->maskingLogger;
        }
        $this->maskingLogger = clone $this->getLoggerInstance();
        $this->loggerFactory->registerWeakReference($this->maskingLogger);
        $processors = array_merge(
            $this->loggerFactory->getMaskingProcessors(),
            ProcessorManager::merge($this->processors, $this->maskingProcessors),
        );

        foreach (array_reverse($processors) as $p) $this->maskingLogger->pushProcessor($p);
        foreach (array_reverse($this->handlers) as $h) $this->maskingLogger->pushHandler($h);

        if ($this->loggerFactory->maskObjects()) {
            $converter = $this->isEnableMasking
                ? new ObjectToMaskedArrayConverterProcessor()
                : new ObjectToArrayConverterProcessor();
            $this->maskingLogger->pushProcessor($converter);
        }
        foreach($this->loggerFactory->getUpdateObjectProcessors() as $processor) {
            $this->maskingLogger->pushProcessor($processor);
        }

        return $this->maskingLogger;
    }

    private function getMonologUnmaskingLogger(): ?MonologLogger
    {
        if ($this->unmaskingLogger) {
            return $this->unmaskingLogger;
        }
        if ($this->hasUnmaskingHandlers()) {
            $this->unmaskingLogger = clone $this->getLoggerInstance();
            $this->loggerFactory->registerWeakReference($this->unmaskingLogger);
            foreach (array_reverse(array_filter($this->processors)) as $p) $this->unmaskingLogger->pushProcessor($p);
            foreach (array_reverse($this->getUnmaskingHandlers()) as $h) $this->unmaskingLogger->pushHandler($h);
        }
        if ($this->unmaskingLogger) {
            if ($this->loggerFactory->maskObjects()) {
                $converter = $this->isEnableMasking
                    ? new ObjectToMaskedArrayConverterProcessor()
                    : new ObjectToArrayConverterProcessor();
                $this->unmaskingLogger->pushProcessor($converter);
            }
            foreach ($this->loggerFactory->getUpdateObjectProcessors() as $processor) {
                $this->unmaskingLogger->pushProcessor($processor);
            }
        }

        return $this->unmaskingLogger;
    }

    /**
     * @return HandlerInterface[]
     */
    private function getUnmaskingHandlers(): array
    {
        return array_merge($this->loggerFactory->getUnmaskingHandlers(), $this->unmaskingHandlers);
    }

    private function hasUnmaskingHandlers(): bool
    {
        return $this->unmaskingHandlers || $this->loggerFactory->getUnmaskingHandlers();
    }

    private function convertLevel(int|Stringable|string|Level $level): string
    {
        if ($level instanceof Level) {
            $level = $level->toPsrLogLevel();
        }
        if (is_int($level)) {
            $level = MonologToPsrLevel::toPsr3($level)->value;
        }
        return (string)$level;
    }

    private function getLoggerInstance(): MonologLogger
    {
        $logger = $this->loggerFactory->getLogger();
        /** Guaranteed that no logger has ever been created. */
        if (!$this->maskingLogger && !$this->unmaskingLogger) {
            if (!$this->exceptionHandler) {
                $this->exceptionHandler = $this->loggerFactory->getExceptionHandler();
            }
        }
        if ($this->name) {
            $logger = $logger->withName($this->name);
        }

        return $logger;
    }

    private function isLevelWithinLimit(string $level): bool
    {
        $levels = LoggingLevel::all();

        return array_search($level, $levels, true)
            >= array_search($this->maxLevel, $levels, true);
    }
}