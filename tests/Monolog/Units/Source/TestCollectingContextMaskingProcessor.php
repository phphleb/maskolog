<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units\Source;

use Maskolog\Processors\AbstractContextMaskingProcessor;

final class TestCollectingContextMaskingProcessor extends AbstractContextMaskingProcessor
{
    /**
     * @var array<int, mixed>
     */
    private $receivedValues = [];

    /**
     * @param mixed $value
     * @return mixed
     */
    public function addMask($value)
    {
        $this->receivedValues[] = $value;

        return $value;
    }

    /**
     * @return mixed
     */
    public function getReceivedValue(int $index)
    {
        return $this->receivedValues[$index] ?? null;
    }
}
