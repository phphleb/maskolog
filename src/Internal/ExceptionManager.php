<?php

namespace Maskolog\Internal;

use Maskolog\Exceptions\MaskingExceptionInterface;
use Maskolog\Logger;

/**
 * Simplifies redirecting collected errors to the logger.
 */
class ExceptionManager
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns an exception object with masking processors from the logger added.
     */
    final public function createMaskedException(
        MaskingExceptionInterface $e
    ): MaskingExceptionInterface
    {
        foreach (array_reverse($this->logger->getMaskingProcessors()) as $processor) {
            $e->pushMaskingProcessor($processor);
        }
        return $e->finalize($this->logger->isEnableMasking());
    }

    /**
     * Throws an exception using masking.
     *
     * @throws MaskingExceptionInterface
     */
    final public function throwMaskedException(
        MaskingExceptionInterface $e
    ): void
    {
        throw $this->createMaskedException($e);
    }
}