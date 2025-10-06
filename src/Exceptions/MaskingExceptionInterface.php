<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Exceptions;

use Maskolog\Logger;
use Maskolog\Processors\MaskingProcessorInterface;

/**
 * Extends the standard exception with the ability to mask the message.
 */
interface MaskingExceptionInterface extends \Throwable
{
    /**
     * Replaces the specified placeholders in the message
     * with the corresponding values.
     * (!) To mask fields, add a masking processor.
     * ```php
     *  throw (new MaskedException('Token output: {token}'))
     *     ->setContext(['token' => 'secret'])
     *     ->finalize();
     * ```
     *
     * @param array<string, int|string> $context
     * @return $this
     */
    public function setContext(#[\SensitiveParameter] array $context): static;

    /**
     * Adds a mask processor for message data via context.
     * ```php
     *   throw (new MaskedException('Token output: {token}'))
     *      ->setContext(['token' => 'secret'])
     *      ->pushMaskingProcessor(new StringMaskingProcessor(['token']))
     *      ->finalize($isEnableMasking);
     * ```
     *
     * @param callable|MaskingProcessorInterface $processor
     * @return $this
     */
    public function pushMaskingProcessor(callable|MaskingProcessorInterface $processor): static;

    /**
     * Applies masking if `isEnableMasking` is active and finalizes masking
     * (no context or processors can be added after this).
     * Can be used together with logger by adding its masking processors
     * and `isEnableMasking` value.
     * Finalizes all previous exceptions if they support it.
     *
     * ```php
     *
     *  $exception = (new MaskedException('Token output: {token}'))
     *      ->setContext(['token' => 'secret']);
     *
     *  $logger->withMaskingProcessors([StringMaskingProcessor::class => 'token'])
     *         ->throwMaskedException($exception);
     *  ```
     */
    public function finalize(bool $isEnableMasking = true): static;

    /**
     * Returns the exception status, whether it has been finalized.
     */
    public function isFinalized(): bool;

    /**
     * Sends exception data to the log using the passed logger.
     *
     * It is assumed that if errors are caught in the application
     * and sent to the log, then this method with the error level
     * will be used for the current type.
     *
     * ```php
     * if ($e instanceof MaskingExceptionInterface) {
     *     $e->sendToLog($maskingLogger, LogLevel::ERROR);
     * } else {
     *     $maskingLogger->error($e);
     * }
     * throw $e;
     *
     * ```
     */
    public function sendToLog(Logger $logger, string $level): void;
}