<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Internal\ExtraModifierProcessor;
use PHPUnit\Framework\TestCase;

class ExtraModifierProcessorTest extends TestCase
{
    use CreateLogRecordTrait;

    public function testEmptyExtraData(): void
    {
        $processor = new ExtraModifierProcessor([]);
        $extra = [];
        $record = $this->createLogRecord(['extra' => $extra]);
        $result =  $processor($record);
        $this->assertEquals($extra, $result->extra);
    }

    public function testOriginExtraData(): void
    {
        $processor = new ExtraModifierProcessor(['cell' => 'origin']);
        $extra = ['cell' => 'replace'];
        $record = $this->createLogRecord(['extra' => $extra]);
        $result =  $processor($record);
        $this->assertEquals(['cell' => 'origin'], $result->extra);
    }

    public function testAddedExtraData(): void
    {
        $processor = new ExtraModifierProcessor(['origin']);
        $extra = ['new'];
        $record = $this->createLogRecord(['extra'  => $extra]);
        $result =  $processor($record);
        $this->assertEquals(['new', 'origin'], $result->extra);
    }
}