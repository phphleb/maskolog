<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Enums\ClassType;
use Maskolog\Enums\StringMaskingStatus;
use Maskolog\Processors\Masking\Context\StringMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\Source\TestObject;
use PHPUnit\Framework\TestCase;

final class StringMaskingProcessorTest extends TestCase
{
    use CreateLogRecordTrait;
    use MixedValuesProviderTrait;

    /**
     * @var StringMaskingProcessor
     */
    private $processor;

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
            StringMaskingStatus::detailedFormat(123, StringMaskingStatus::INVALID_TYPE),
            $this->processor->addMask(123)
        );
        $this->assertSame(
            StringMaskingStatus::format(['array'], StringMaskingStatus::INVALID_TYPE),
            $this->processor->addMask(['array'])
        );
        $this->assertSame(
            StringMaskingStatus::format(new \stdClass(), StringMaskingStatus::INVALID_TYPE),
            $this->processor->addMask(new \stdClass())
        );
        $this->assertSame(
            StringMaskingStatus::format(new \stdClass(), StringMaskingStatus::INVALID_TYPE),
            $this->processor->addMask(new \stdClass())
        );
        $class = new class {};
        $this->assertSame(
            StringMaskingStatus::detailedFormat($class, StringMaskingStatus::INVALID_TYPE),
            $this->processor->addMask($class)
        );
        $object = new TestObject();
        $this->assertSame(
            StringMaskingStatus::detailedFormat([TestObject::class => []], StringMaskingStatus::INVALID_TYPE),
            $this->processor->addMask($object)
        );
        $this->assertSame(
            StringMaskingStatus::detailedFormat([ClassType::ANONYMOUS => []], StringMaskingStatus::INVALID_TYPE),
            $this->processor->addMask($class)
        );
    }

    public function testStringsReplacedCorrectly(): void
    {
        $replacement = StringMaskingStatus::REPLACEMENT;

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
        $result = ['context' => $processor($record)['context']];

        $this->assertEquals($checkData, $result);
      }

    /**
     * @dataProvider mixedValues
     */
    public function testStringMaskingWithMixed($value): void
    {
        if (!$value) {
            $this->assertSame(
                $value,
                $this->processor->addMask($value)
            );
            return;
        } else if (!is_string($value)) {
            $this->assertSame(
                StringMaskingStatus::detailedFormat($value, StringMaskingStatus::INVALID_TYPE),
                $this->processor->addMask($value)
            );
            return;
        }
        $this->expectNotToPerformAssertions();
    }
}
