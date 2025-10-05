<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Enums\ClassType;
use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Enums\StringMaskingStatus;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use Maskolog\Processors\Masking\Context\StringMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\Source\TestObject;
use PHPUnit\Framework\TestCase;

final class StringMaskingProcessorTest extends TestCase
{
    use CreateLogRecordTrait;
    use MixedValuesProviderTrait;

    private StringMaskingProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new StringMaskingProcessor();
    }

    public function testEmptyValueReturnsSameValue(): void
    {
        $this->assertSame(null, $this->processor->addMask(null));
        $this->assertSame('', $this->processor->addMask(''));
        $this->assertSame(false, $this->processor->addMask(false));
        $this->assertSame(0, $this->processor->addMask(0));
    }

    public function testInvalidTypeReturnsInvalidTypeStatus(): void
    {
        $this->assertSame(
            StringMaskingStatus::INVALID_TYPE->detailedFormat(123),
            $this->processor->addMask(123)
        );
        $this->assertSame(
            StringMaskingStatus::INVALID_TYPE->format(['array']),
            $this->processor->addMask(['array'])
        );
        $this->assertSame(
            StringMaskingStatus::INVALID_TYPE->format(new \stdClass()),
            $this->processor->addMask(new \stdClass())
        );
        $this->assertSame(
            StringMaskingStatus::INVALID_TYPE->format(new \stdClass()),
            $this->processor->addMask(new \stdClass())
        );
        $class = new class {};
        $this->assertSame(
            StringMaskingStatus::INVALID_TYPE->detailedFormat($class),
            $this->processor->addMask($class)
        );
        $object = new TestObject();
        $this->assertSame(
            StringMaskingStatus::INVALID_TYPE->detailedFormat([TestObject::class => []]),
            $this->processor->addMask($object)
        );
        $class = new class {};
        $this->assertSame(
            StringMaskingStatus::INVALID_TYPE->detailedFormat([ClassType::ANONYMOUS->value => []]),
            $this->processor->addMask($class)
        );
    }

    public function testStringsReplacedCorrectly(): void
    {
        $replacement = StringMaskingStatus::REPLACEMENT->value;

        $this->assertSame(
            'M' . $replacement . 'tr',
            $this->processor->addMask('MyStr')
        );
        $this->assertSame(
            'E' . $replacement . 'de',
            $this->processor->addMask('Encode')
        );
        $this->assertSame(
            'П' . $replacement . 'ль',
            $this->processor->addMask('Пароль')
        );
        $this->assertSame(
            'T' . $replacement . 'ng',
            $this->processor->addMask('Toolong')
        );
        $this->assertSame(
            'Pas' . $replacement . 'rd',
            $this->processor->addMask('Password')
        );
        $this->assertSame(
            'Thi' . $replacement . 'st',
            $this->processor->addMask('ThisIsALongTest')
        );
    }

    public function testVariableSimpleLabels()
    {
        $processor = new StringMaskingProcessor(['token', 'other']);
        $maskedValue = $processor->addMask('m23eh7ph3');

        $data = ['context' => ['token' => 'm23eh7ph3']];
        $checkData = ['context' => ['token' => $maskedValue]];
        $record = $this->createLogRecord($data);
        $result = ['context' => $processor($record)->context];

        $this->assertEquals($checkData, $result);
      }

    /**
     * @dataProvider mixedValues
     */
    public function testStringMaskingWithMixed(mixed $value): void
    {
        if (!$value) {
            $this->assertSame(
                $value,
                $this->processor->addMask($value)
            );
            return;
        } else if (!is_string($value)) {
            $this->assertSame(
                StringMaskingStatus::INVALID_TYPE->detailedFormat($value),
                $this->processor->addMask($value)
            );
            return;
        }
        $this->expectNotToPerformAssertions();
    }
}
