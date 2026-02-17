<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Exceptions;

use Maskolog\Internal\Exceptions\LogicException;
use Maskolog\Processors\MaskingProcessorInterface;
use Maskolog\Logger;
use Psr\Log\LogLevel;

/**
 * Implements the methods specified in the MaskingExceptionInterface interface
 * for shared use in custom maskable errors.
 *
 * @property string $message
 */
trait MaskingExceptionTrait
{
    /** @var string  */
    protected $pattern = '{%s}';

    /**
     * @var array<string, int|string> $context
     */
    protected $context = [];

    /**
     * @var (callable|MaskingProcessorInterface)[]
     */
    protected $processors = [];

    /**
     * @var bool
     */
    protected $finalized = false;

    /**
     * @var string|null
     */
    protected $rawMessage = null;

    /**
     * @inheritDoc
     * @param array<string, int|string> $context
     */
    final public function setContext(array $context): MaskingExceptionInterface
    {
        if ($this->finalized) throw new LogicException('Unable to add context after finalization');

        $this->context = $context;

        $this->rawMessage = $this->rawMessage ?? $this->message;

        return $this;
    }

    /**
     * @inheritDoc
     * @param callable|MaskingProcessorInterface $processor
     */
    final public function pushMaskingProcessor($processor): MaskingExceptionInterface
    {
        if ($this->finalized) throw new LogicException('Unable to add processors after finalization');

        array_unshift($this->processors, $processor);

        $this->rawMessage = $this->rawMessage ?? $this->message;

        return $this;
    }

    /**
     * @inheritDoc
     */
    final public function isFinalized(): bool
    {
        return $this->finalized;
    }

    /**
     * @inheritDoc
     */
    final public function sendToLog(Logger $logger, string $level = LogLevel::ERROR): void
    {
        $this->processors and $logger = $logger->withMaskingProcessors($this->processors);

        $logger->log($level, $this->rawMessage ?? $this->message, $this->context);
    }

    /**
     * @inheritDoc
     */
    final public function finalize(bool $isEnableMasking = true): MaskingExceptionInterface
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
                      $record = $processor($record);
                }
            }
        }
        $message = $record['message'];
        $context = $record['context'];

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
     * @return array<string, mixed>
     */
    private function createRecord(): array
    {
        return [
            'message' => $this->message,
            'context' => $this->context,
            'level' => \Monolog\Logger::ERROR,
            'level_name' => 'ERROR',
            'channel' => 'exception',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];
    }
}