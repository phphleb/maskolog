<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Maskolog\Exceptions\Handlers\LoggerExceptionHandlerInterface;
use Throwable;

class TestExceptionHandler implements LoggerExceptionHandlerInterface
{
    public function handle(Throwable $e, array $rawLog): void
    {
        throw new ResultException($e->getMessage());
    }
}