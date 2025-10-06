<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Exceptions;

use Maskolog\Internal\Exceptions\LogicException;
use Maskolog\Logger;
use Maskolog\Processors\MaskingProcessorInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Log\LogLevel;

/**
 * Implements the methods specified in the MaskingExceptionInterface interface
 * for shared use in custom maskable errors.
 *
 * @property string $message
 */
trait MaskingExceptionTrait
{
    protected string $pattern = '{%s}';

    /**
     * @var array<string, int|string> $context
     */
    protected array $context = [];

    /**
     * @var (callable|MaskingProcessorInterface)[]
     */
    protected array $processors = [];

    protected bool $finalized = false;

    protected ?string $rawMessage = null;

    /**
     * @inheritDoc
     * @param array<string, int|string> $context
     */
    #[\Override]
    final public function setContext(#[\SensitiveParameter] array $context): static
    {
        $this->finalized and throw new LogicException('Unable to add context after finalization');

        $this->context = $context;

        $this->rawMessage = $this->rawMessage ?? $this->message;

        return $this;
    }

    /**
     * @inheritDoc
     * @param callable|MaskingProcessorInterface $processor
     */
    #[\Override]
    final public function pushMaskingProcessor(callable|MaskingProcessorInterface $processor): static
    {
        $this->finalized and throw new LogicException('Unable to add processors after finalization');

        array_unshift($this->processors, $processor);

        $this->rawMessage = $this->rawMessage ?? $this->message;

        return $this;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    final public function isFinalized(): bool
    {
        return $this->finalized;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    final public function sendToLog(Logger $logger, string $level = LogLevel::ERROR): void
    {
        $this->processors and $logger = $logger->withMaskingProcessors($this->processors);

        $logger->log($level, $this->rawMessage ?? $this->message, $this->context);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    final public function finalize(bool $isEnableMasking = true): static
    {
        // This is an idempotent method.
        if ($this->finalized) {
            return $this;
        }
        $this->finalized = true;

        // Finalizes all previous exceptions if they support it.
        $previous = $this->getPrevious();
        if ($previous instanceof MaskingExceptionInterface) {
            $previous->finalize($isEnableMasking);
        }

        $record = $this->createRecord();
        if ($isEnableMasking) {
            if (count($this->processors)) {
                foreach ($this->processors as $processor) {
                    /** @var callable $processor */
                    /** @var LogRecord $record */
                    $record = $processor($record);
                }
            }
        }
        $message = $record->message;
        $context = $record->context;

        /**
         * @var string $key
         * @var int|string $replace
         */
        foreach ($context as $key => $replace) {
            $search = sprintf($this->pattern, $key);
            $message = str_replace($search, (string)$replace, $message);
        }
        $this->message = $message;

        return $this;
    }

    /**
     * @return LogRecord
     */
    private function createRecord(): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'exception',
            level: Level::Error,
            message: $this->message,
            context: $this->context,
        );
    }
}