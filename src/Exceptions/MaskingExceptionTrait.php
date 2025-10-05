<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Exceptions;

use Maskolog\Internal\Exceptions\LogicException;
use Maskolog\Processors\MaskingProcessorInterface;
use Monolog\Level;
use Monolog\LogRecord;

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

    private bool $finalized = false;

    /**
     * @inheritDoc
     * @param array<string, int|string> $context
     */
    final public function setContext(#[\SensitiveParameter] array $context): static
    {
        $this->finalized and throw new LogicException('Unable to add context after finalization');

        $this->context = $context;

        return $this;
    }

    /**
     * @inheritDoc
     * @param callable|MaskingProcessorInterface $processor
     */
    final public function pushMaskingProcessor(callable|MaskingProcessorInterface $processor): static
    {
        $this->finalized and throw new LogicException('Unable to add processors after finalization');

        array_unshift($this->processors, $processor);

        return $this;
    }

    /**
     * @inheritDoc
     */
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