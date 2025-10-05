<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use DateTime;
use Maskolog\Enums\ClassType;
use Maskolog\Internal\ObjectToArrayConverterProcessor;
use MaskologLoggerTests\Monolog\Functional\Source\TestObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class ObjectToArrayConverterProcessorTest extends TestCase
{
   use CreateLogRecordTrait;

   public function testSerializeConverter(): void
   {
       $result = unserialize(serialize(new ObjectToArrayConverterProcessor()));
       $this->assertTrue($result instanceof ObjectToArrayConverterProcessor);
   }

   public function testConvertEmptyAnonymousClassObject(): void
   {
       $converter = new ObjectToArrayConverterProcessor();
       $object = new class {};
       $context = [$object];
       $converter->update($context);
       $this->assertEquals([[ClassType::ANONYMOUS => []]], $context);
   }

    public function testConvertAnonymousClassObject(): void
    {
        $converter = new ObjectToArrayConverterProcessor();
        $object = new class {
            public string $cell = 'secret_cell';
        };
        $context = [$object];
        $converter->update($context);
        $this->assertEquals([[ClassType::ANONYMOUS => ['cell' => 'secret_cell']]], $context);
    }

    public function testConvertStdClassObject(): void
    {
        $converter = new ObjectToArrayConverterProcessor();
        $object = new stdClass();
        $object->cell = 'secret_cell';
        $context = [$object];
        $converter->update($context);
        $this->assertEquals([[stdClass::class => ['cell' => 'secret_cell']]], $context);
    }

    public function testConvertDateTimeObject(): void
    {
        $converter = new ObjectToArrayConverterProcessor();
        $object = new DateTime();
        $context = [$object];
        $converter->update($context);
        $this->assertEquals([[DateTime::class => ['date' => $object->format(\DATE_ATOM)]]], $context);
    }

    public function testConvertClassObject(): void
    {
        $converter = new ObjectToArrayConverterProcessor();
        $object = new TestObject();
        $context = [$object];
        $converter->update($context);
        $this->assertEquals([[TestObject::class => $object->toArray()]], $context);
    }

    public function testConvertEmptyAnonymousObjectInRecord(): void
    {
        $processor = new ObjectToArrayConverterProcessor();
        $object = new class {};
        $record = $this->createLogRecord(['context' => [$object]]);
        $result = $processor($record);
        $this->assertEquals([[ClassType::ANONYMOUS => []]], $result['context']);
    }

    public function testConvertObjectInRecord(): void
    {
        $processor = new ObjectToArrayConverterProcessor();
        $object = new TestObject();
        $record = $this->createLogRecord(['context' => [$object]]);
        $result = $processor($record);
        $this->assertEquals([[TestObject::class => $object->toArray()]], $result['context']);
    }
}