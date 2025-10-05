<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use DateTime;
use Maskolog\Enums\ClassType;
use Maskolog\Internal\ObjectToArrayConverter;
use MaskologLoggerTests\Monolog\Functional\Source\TestObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class ObjectToArrayConverterTest extends TestCase
{
   public function testSerializeConverter(): void
   {
       $result = unserialize(serialize(new ObjectToArrayConverter()));
       $this->assertTrue($result instanceof ObjectToArrayConverter);
   }

   public function testConvertEmptyAnonymousClassObject(): void
   {
       $converter = new ObjectToArrayConverter();
       $object = new class {};
       $result = $converter->convert($object);
       $this->assertEquals([ClassType::ANONYMOUS->value => []], $result);
   }

    public function testConvertAnonymousClassObject(): void
    {
        $converter = new ObjectToArrayConverter();
        $object = new class {
            public string $cell = 'secret_cell';
        };
        $result = $converter->convert($object);
        $this->assertEquals([ClassType::ANONYMOUS->value => ['cell' => 'secret_cell']], $result);
    }

    public function testConvertStdClassObject(): void
    {
        $converter = new ObjectToArrayConverter();
        $object = new stdClass();
        $object->cell = 'secret_cell';
        $result = $converter->convert($object);
        $this->assertEquals([stdClass::class => ['cell' => 'secret_cell']], $result);
    }

    public function testConvertDateTimeObject(): void
    {
        $converter = new ObjectToArrayConverter();
        $object = new DateTime();
        $result = $converter->convert($object);
        $this->assertEquals([DateTime::class => ['date' => $object->format(\DATE_ATOM)]], $result);
    }

    public function testConvertClassObject(): void
    {
        $converter = new ObjectToArrayConverter();
        $object = new TestObject();
        $result = $converter->convert($object);
        $this->assertEquals([TestObject::class => $object->toArray()], $result);
    }
}