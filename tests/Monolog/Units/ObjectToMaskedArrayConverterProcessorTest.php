<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use DateTime;
use Maskolog\Enums\ClassType;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use Maskolog\Processors\Masking\Context\MaskingProcessor;
use Maskolog\Internal\ObjectToMaskedArrayConverterProcessor;
use MaskologLoggerTests\Monolog\Functional\Source\TestNestedObject;
use MaskologLoggerTests\Monolog\Functional\Source\TestObject;
use MaskologLoggerTests\Monolog\Functional\Source\TestObjectWithAttributes;
use PHPUnit\Framework\TestCase;
use stdClass;

class ObjectToMaskedArrayConverterProcessorTest extends TestCase
{
   use CreateLogRecordTrait;

   public function testSerializeConverter(): void
   {
       $result = unserialize(serialize(new ObjectToMaskedArrayConverterProcessor()));
       $this->assertTrue($result instanceof ObjectToMaskedArrayConverterProcessor);
   }

   public function testConvertEmptyAnonymousClassObject(): void
   {
       $converter = new ObjectToMaskedArrayConverterProcessor();
       $object = new class {};
       $context = [$object];
       $converter->update($context);
       $this->assertEquals([[ClassType::ANONYMOUS->value => []]], $context);
   }

    public function testConvertAnonymousClassObject(): void
    {
        $converter = new ObjectToMaskedArrayConverterProcessor();
        $object = new class {
            public string $cell = 'secret_cell';
        };
        $context = [$object];
        $converter->update($context);
        $this->assertEquals([[ClassType::ANONYMOUS->value => ['cell' => 'secret_cell']]], $context);
    }

    public function testConvertStdClassObject(): void
    {
        $converter = new ObjectToMaskedArrayConverterProcessor();
        $object = new stdClass();
        $object->cell = 'secret_cell';
        $context = [$object];
        $converter->update($context);
        $this->assertEquals([[stdClass::class => ['cell' => 'secret_cell']]], $context);
    }

    public function testConvertDateTimeObject(): void
    {
        $converter = new ObjectToMaskedArrayConverterProcessor();
        $object = new DateTime();
        $context = [$object];
        $converter->update($context);
        $this->assertEquals([[DateTime::class => ['date' => $object->format(\DATE_ATOM)]]], $context);
    }

    public function testConvertClassObject(): void
    {
        $converter = new ObjectToMaskedArrayConverterProcessor();
        $object = new TestObject();
        $context = [$object];
        $converter->update($context);
        $this->assertEquals([[TestObject::class => $object->toArray()]], $context);
    }

    public function testConvertEmptyAnonymousObjectInRecord(): void
    {
        $processor = new ObjectToMaskedArrayConverterProcessor();
        $object = new class {};
        $record = $this->createLogRecord(['context' => [$object]]);
        $result = $processor($record);
        $this->assertEquals([[ClassType::ANONYMOUS->value => []]], $result->context);
    }

    public function testConvertObjectInRecord(): void
    {
        $processor = new ObjectToMaskedArrayConverterProcessor();
        $object = new TestObject();
        $record = $this->createLogRecord(['context' => [$object]]);
        $result = $processor($record);
        $this->assertEquals([[TestObject::class => $object->toArray()]], $result->context);
    }

    public function testMaskObjectInRecord(): void
    {
        $processor = new ObjectToMaskedArrayConverterProcessor();
        $object = new TestObjectWithAttributes();
        $record = $this->createLogRecord(['context' => [$object]]);
        $result = $processor($record);
        $expected = [[TestObjectWithAttributes::class => [
            'publicReadonlySecretCell' => (new MaskingProcessor())->addMask('public_readonly_secret_data'),
            'publicSecretPassword' => (new PasswordMaskingProcessor())->addMask('public_secret_data'),
            'publicCell' => 'public_data',
        ]]];
        $this->assertEquals($expected, $result->context);
    }

    public function testMaskNestedObjectInRecord(): void
    {
        $processor = new ObjectToMaskedArrayConverterProcessor();
        $object = new TestNestedObject();
        $nestedObject = new TestObjectWithAttributes();
        $object->maskObject = $nestedObject;
        $object->nestedObject = $nestedObject;
        $record = $this->createLogRecord(['context' => [$object]]);
        $result = $processor($record);
        $expected = [
            [TestNestedObject::class => [
                'publicSecretCell' => (new MaskingProcessor())->addMask('public_secret_data'),
                'maskObject' => (new MaskingProcessor())->addMask($nestedObject),
                'nestedObject' =>
            [TestObjectWithAttributes::class => [
            'publicReadonlySecretCell' => (new MaskingProcessor())->addMask('public_readonly_secret_data'),
            'publicSecretPassword' => (new PasswordMaskingProcessor())->addMask('public_secret_data'),
            'publicCell' => 'public_data',
        ]]]]];
        $this->assertEquals($expected, $result->context);
    }
}