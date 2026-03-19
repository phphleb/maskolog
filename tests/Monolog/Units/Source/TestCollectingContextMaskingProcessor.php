<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units\Source;

use Maskolog\Processors\AbstractContextMaskingProcessor;

final class TestCollectingContextMaskingProcessor extends AbstractContextMaskingProcessor
{
    /**
     * @var array<int, mixed>
     */
    private array $receivedValues = [];

    /**
     * @param mixed $value
     * @return mixed
     */
    public function addMask(mixed $value): mixed
    {
        $this->receivedValues[] = $value;

        return $value;
    }

    public function getReceivedValue(int $index): mixed
    {
        return $this->receivedValues[$index] ?? null;
    }
}
