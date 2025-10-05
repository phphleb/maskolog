<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Enums\ClassType;
use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\Source\TestObject;
use PHPUnit\Framework\TestCase;

final class PasswordMaskingProcessorTest extends TestCase
{
    use CreateLogRecordTrait;
    use MixedValuesProviderTrait;

    private PasswordMaskingProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new PasswordMaskingProcessor();
    }

    public function testEmptyPasswordReturnsEmptyStatus(): void
    {
        $this->assertSame(
            PasswordMaskingStatus::EMPTY_PASSWORD->format(null),
            $this->processor->addMask(null)
        );
        $this->assertSame(
            PasswordMaskingStatus::EMPTY_PASSWORD->format(''),
            $this->processor->addMask('')
        );
        $this->assertSame(
            PasswordMaskingStatus::EMPTY_PASSWORD->format(0),
            $this->processor->addMask(0)
        );
        $this->assertSame(
            PasswordMaskingStatus::EMPTY_PASSWORD->format(false),
            $this->processor->addMask(false)
        );
        $this->assertSame(
            PasswordMaskingStatus::EMPTY_PASSWORD->format([]),
            $this->processor->addMask([])
        );
    }

    public function testInvalidTypeReturnsInvalidTypeStatus(): void
    {
        $this->assertSame(
            PasswordMaskingStatus::INVALID_TYPE_PASSWORD->format(1234),
            $this->processor->addMask(1234)
        );
        $this->assertSame(
            PasswordMaskingStatus::INVALID_TYPE_PASSWORD->format(['secret']),
            $this->processor->addMask(['secret'])
        );
        $this->assertSame(
            PasswordMaskingStatus::INVALID_TYPE_PASSWORD->format(new \stdClass()),
            $this->processor->addMask(new \stdClass())
        );
        $class = new class {};
        $this->assertSame(
            PasswordMaskingStatus::INVALID_TYPE_PASSWORD->detailedFormat($class),
            $this->processor->addMask($class)
        );
        $object = new TestObject();
        $this->assertSame(
            PasswordMaskingStatus::INVALID_TYPE_PASSWORD->detailedFormat([TestObject::class => []]),
            $this->processor->addMask($object)
        );
        $class = new class {};
        $this->assertSame(
            PasswordMaskingStatus::INVALID_TYPE_PASSWORD->detailedFormat([ClassType::ANONYMOUS->value => []]),
            $this->processor->addMask($class)
        );
    }

    public function testInvalidLengthReturnsInvalidLengthStatus(): void
    {
        $this->assertSame(
            PasswordMaskingStatus::INVALID_LENGTH_PASSWORD->strlen('short'),
            $this->processor->addMask('short')
        );
        $this->assertSame(
            PasswordMaskingStatus::INVALID_LENGTH_PASSWORD->strlen('1234567'),
            $this->processor->addMask('1234567')
        );
        $this->assertSame(
            PasswordMaskingStatus::INVALID_LENGTH_PASSWORD->strlen('qwerty'),
            $this->processor->addMask('qwerty')
        );
    }

    public function testMaskedPasswordReturnsMask(): void
    {
        $this->assertSame(
            PasswordMaskingStatus::MASKED_PASSWORD->value,
            $this->processor->addMask('superpassword')
        );
        $this->assertSame(
            PasswordMaskingStatus::MASKED_PASSWORD->value,
            $this->processor->addMask('12345678')
        );
        $this->assertSame(
            PasswordMaskingStatus::MASKED_PASSWORD->value,
            $this->processor->addMask(str_repeat('a', 16))
        );
        $this->assertSame(
            PasswordMaskingStatus::MASKED_PASSWORD->value,
            $this->processor->addMask('%sОченьСложныйПароль#-$v123')
        );
    }

    public function testVariableSimpleLabels()
    {
        $replacement = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $processor = new PasswordMaskingProcessor(['password', 'other']);

        $data = ['context' => ['password' => 'm23eh7ph3']];
        $checkData = ['context' => ['password' => $replacement]];

        $record = $this->createLogRecord($data);
        $result = ['context' => $processor($record)->context];

        $this->assertEquals($checkData, $result);
    }

    /**
     * @dataProvider mixedValues
     */
    public function testPasswordMaskingWithMixed(mixed $value): void
    {
        if (!$value) {
            $this->assertSame(
                PasswordMaskingStatus::EMPTY_PASSWORD->detailedFormat($value),
                $this->processor->addMask($value)
            );
        } else if (!is_string($value)) {
            $this->assertSame(
                PasswordMaskingStatus::INVALID_TYPE_PASSWORD->detailedFormat($value),
                $this->processor->addMask($value)
            );
        } else if (mb_strlen($value) < PasswordMaskingProcessor::MIN_PASSWORD_LENGTH) {
            $this->assertSame(
                PasswordMaskingStatus::INVALID_LENGTH_PASSWORD->strlen($value),
                $this->processor->addMask($value)
            );
        } else {
            $this->assertSame(
                PasswordMaskingStatus::MASKED_PASSWORD->value,
                $this->processor->addMask($value)
            );
        }
    }
}
