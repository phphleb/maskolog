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

    /**
     * @var PasswordMaskingProcessor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new PasswordMaskingProcessor();
    }

    public function testEmptyPasswordReturnsEmptyStatus(): void
    {
        $this->assertSame(
            PasswordMaskingStatus::format(null, PasswordMaskingStatus::EMPTY_PASSWORD),
            $this->processor->addMask(null)
        );
        $this->assertSame(
            PasswordMaskingStatus::format('', PasswordMaskingStatus::EMPTY_PASSWORD),
            $this->processor->addMask('')
        );
        $this->assertSame(
            PasswordMaskingStatus::format(0, PasswordMaskingStatus::EMPTY_PASSWORD),
            $this->processor->addMask(0)
        );
        $this->assertSame(
            PasswordMaskingStatus::format(false, PasswordMaskingStatus::EMPTY_PASSWORD),
            $this->processor->addMask(false)
        );
        $this->assertSame(
            PasswordMaskingStatus::format([], PasswordMaskingStatus::EMPTY_PASSWORD),
            $this->processor->addMask([])
        );
    }

    public function testInvalidTypeReturnsInvalidTypeStatus(): void
    {
        $this->assertSame(
            PasswordMaskingStatus::format(1234, PasswordMaskingStatus::INVALID_TYPE_PASSWORD),
            $this->processor->addMask(1234)
        );
        $this->assertSame(
            PasswordMaskingStatus::format(['secret'], PasswordMaskingStatus::INVALID_TYPE_PASSWORD),
            $this->processor->addMask(['secret'])
        );
        $this->assertSame(
            PasswordMaskingStatus::format(new \stdClass(), PasswordMaskingStatus::INVALID_TYPE_PASSWORD),
            $this->processor->addMask(new \stdClass())
        );
        $class = new class {};
        $this->assertSame(
            PasswordMaskingStatus::detailedFormat($class, PasswordMaskingStatus::INVALID_TYPE_PASSWORD),
            $this->processor->addMask($class)
        );
        $object = new TestObject();
        $this->assertSame(
            PasswordMaskingStatus::detailedFormat([TestObject::class => []], PasswordMaskingStatus::INVALID_TYPE_PASSWORD),
            $this->processor->addMask($object)
        );
        $this->assertSame(
            PasswordMaskingStatus::detailedFormat([ClassType::ANONYMOUS => []], PasswordMaskingStatus::INVALID_TYPE_PASSWORD),
            $this->processor->addMask($class)
        );
    }

    public function testInvalidLengthReturnsInvalidLengthStatus(): void
    {
        $this->assertSame(
            PasswordMaskingStatus::strlen('short', PasswordMaskingStatus::INVALID_LENGTH_PASSWORD),
            $this->processor->addMask('short')
        );
        $this->assertSame(
            PasswordMaskingStatus::strlen('1234567', PasswordMaskingStatus::INVALID_LENGTH_PASSWORD),
            $this->processor->addMask('1234567')
        );
        $this->assertSame(
            PasswordMaskingStatus::strlen('qwerty', PasswordMaskingStatus::INVALID_LENGTH_PASSWORD),
            $this->processor->addMask('qwerty')
        );
    }

    public function testMaskedPasswordReturnsMask(): void
    {
        $this->assertSame(
            PasswordMaskingStatus::MASKED_PASSWORD,
            $this->processor->addMask('superpassword')
        );
        $this->assertSame(
            PasswordMaskingStatus::MASKED_PASSWORD,
            $this->processor->addMask('12345678')
        );
        $this->assertSame(
            PasswordMaskingStatus::MASKED_PASSWORD,
            $this->processor->addMask(str_repeat('a', 16))
        );
        $this->assertSame(
            PasswordMaskingStatus::MASKED_PASSWORD,
            $this->processor->addMask('%sОченьСложныйПароль#-$v123')
        );
    }

    public function testVariableSimpleLabels()
    {
        $replacement = PasswordMaskingStatus::MASKED_PASSWORD;
        $processor = new PasswordMaskingProcessor(['password', 'other']);

        $data = ['context' => ['password' => 'm23eh7ph3']];
        $checkData = ['context' => ['password' => $replacement]];

        $record = $this->createLogRecord($data);
        $result = ['context' => $processor($record)['context']];

        $this->assertEquals($checkData, $result);
    }

    /**
     * @dataProvider mixedValues
     */
    public function testPasswordMaskingWithMixed($value): void
    {
        if (!$value) {
            $this->assertSame(
                PasswordMaskingStatus::detailedFormat($value, PasswordMaskingStatus::EMPTY_PASSWORD),
                $this->processor->addMask($value)
            );
        } else if (!is_string($value)) {
            $this->assertSame(
                PasswordMaskingStatus::detailedFormat($value, PasswordMaskingStatus::INVALID_TYPE_PASSWORD),
                $this->processor->addMask($value)
            );
        } else if (mb_strlen($value) < PasswordMaskingProcessor::MIN_PASSWORD_LENGTH) {
            $this->assertSame(
                PasswordMaskingStatus::strlen($value, PasswordMaskingStatus::INVALID_LENGTH_PASSWORD),
                $this->processor->addMask($value)
            );
        } else {
            $this->assertSame(
                PasswordMaskingStatus::MASKED_PASSWORD,
                $this->processor->addMask($value)
            );
        }
    }
}
