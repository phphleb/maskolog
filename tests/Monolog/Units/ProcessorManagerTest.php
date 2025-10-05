<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Enums\StringMaskingStatus;
use Maskolog\Internal\ProcessorManager;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use Maskolog\Processors\Masking\Context\StringMaskingProcessor;
use PHPUnit\Framework\TestCase;

class ProcessorManagerTest extends TestCase
{
    use CreateLogRecordTrait;

   public function testDefaultEmptyData()
   {
       $result = ProcessorManager::merge([], []);

       $this->assertEquals([], $result);
   }

    public function testCompareOneMaskingProcessorData()
    {
        $processors = [null];
        $maskingProcessors = [[PasswordMaskingProcessor::class => 'password']];

        $resultProcessors = ProcessorManager::merge($processors, $maskingProcessors);

        $this->assertCount(1, $resultProcessors);

        $resultProcessor = current($resultProcessors);

        $record = $this->createLogRecord(['context' => ['password' => 'secret_password']]);
        $context = ['context' => $resultProcessor($record)->context];

        $this->assertEquals(['context' => ['password' => PasswordMaskingStatus::MASKED_PASSWORD->value]], $context);
    }

    public function testOnlyMaskingProcessorData()
    {
        $processors = [];
        $maskingProcessors = [[PasswordMaskingProcessor::class => 'password']];

        $resultProcessors = ProcessorManager::merge($processors, $maskingProcessors);

        $this->assertCount(0, $resultProcessors);
    }

    public function testVariableProcessorsData()
    {
        $callable =  static function ($record) {  return ['context' => $record];  };
        $processors = [null, $callable, null];
        $maskingProcessors = [[PasswordMaskingProcessor::class => 'password'], [StringMaskingProcessor::class => ['token']]];

        $resultProcessors = ProcessorManager::merge($processors, $maskingProcessors);

        $this->assertCount(3, $resultProcessors);

        [$firstProcessor, $secondProcessor, $thirdProcessor] = $resultProcessors;

        $record = $this->createLogRecord(['context' => ['password' => 'secret_password']]);
        $this->assertEquals(
            ['context' => ['password' => PasswordMaskingStatus::MASKED_PASSWORD->value]],
            ['context' => $firstProcessor($record)->context],
        );

        $this->assertEquals(
            ['context' => __FUNCTION__],
            $secondProcessor(__FUNCTION__),
        );

        $record = $this->createLogRecord(['context' => ['token' => 'm23eh7ph3']]);
        $this->assertEquals(
            ['context' => ['token' => 'm23' . StringMaskingStatus::REPLACEMENT->value . 'h3']],
            ['context' => $thirdProcessor($record)->context],
        );
    }

    public function testConvertMaskingProcessors(): void
    {
        $maskingProcessors = [[PasswordMaskingProcessor::class => 'password']];

        $resultProcessors = ProcessorManager::convert($maskingProcessors);

        $this->assertCount(1, $resultProcessors);
        $processor = end($resultProcessors);
        $this->assertTrue(is_object($processor));
        $this->assertTrue($processor instanceof PasswordMaskingProcessor);
    }

    public function testSimpleRemoveDuplicates(): void
    {
        $class = PasswordMaskingProcessor::class;
        $result = ProcessorManager::removeDuplicates(
            [[$class => ['first', 'second', 'list' => ['value1', 'value2']]]],
            $class, ['first', 'other', 'list' => ['value1', 'value3']]
        );
        $combine = [[$class => ['first', 'second', 'other', 'list' => ['value1', 'value2', 'value3']]]];

        self::assertSame($result, $combine);

        $result = ProcessorManager::removeDuplicates(
            [[$class => ['first', ['second'], 'list' => ['value1', ['value2']]]]],
            $class, ['first', 'other', ['second'], 'list' => ['value1', 'value3']]
        );
        $combine = [[$class => ['first', ['second'], 'other', 'list' => ['value1', ['value2'], 'value3']]]];

        self::assertSame($result, $combine);

        $result = ProcessorManager::removeDuplicates(
            [[$class => [['first'], 'first', 'list' => ['value1' => ['value3']]]]],
            $class, ['first', 'other', 'second', 'list' => ['value1']]
        );
        $combine = [[$class => [['first'], 'first', 'other', 'second', 'list' => ['value1', 'value1' => ['value3']]]]];

        self::assertSame($result, $combine);

        $result = ProcessorManager::removeDuplicates(
            [[$class => ['first', 'list' => ['value1' => 'value2']]]],
            $class, ['first', 'list' => ['value1' => 'value3']]
        );
        $combine = [[$class => ['first', 'list' => ['value1' => ['value2', 'value3']]]]];

        self::assertSame($result, $combine);
    }

    public function testRemoveBlankDuplicates(): void
    {
        $class = PasswordMaskingProcessor::class;
        $result = ProcessorManager::removeDuplicates([[$class => []]], $class, []);
        $combine = [[$class => []]];

        self::assertSame($result, $combine);

        $result = ProcessorManager::removeDuplicates([[$class => []]], $class, ['test' => 1000]);
        $combine = [[$class => ['test' => 1000]]];

        self::assertSame($result, $combine);

        $result = ProcessorManager::removeDuplicates([[$class => ['test' => 1000]]], $class, []);

        self::assertSame($result, $combine);
    }
}