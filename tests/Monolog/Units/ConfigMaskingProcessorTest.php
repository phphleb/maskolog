<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Processors\Masking\Config\ConfigMaskingProcessor;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use Maskolog\Processors\Masking\Context\MaskingProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class ConfigMaskingProcessorTest extends TestCase
{
    use CreateLogRecordTrait;

    public function testEmptyConfigAndDefaultLevels(): void
    {
        $configMaskingProcessor = new ConfigMaskingProcessor([]);
        $record = $this->createLogRecord([]);
        $result = $configMaskingProcessor($record);
        $context = $result['context'];
        $this->assertSame([], $context);
    }

    public function testVariableMaskingConfig(): void
    {
        $config = ['secret_value'];
        $configMaskingProcessor = new ConfigMaskingProcessor($config);
        $record = $this->createLogRecord(['context' => ['secret_value', 'level' => ['secret_value', '_secret_value_', 'other_value']]]);
        $expected = (new MaskingProcessor())->addMask('secret_value');
        $result = $configMaskingProcessor($record);
        $context = $result['context'];
        $this->assertSame([$expected, 'level' => [$expected, '_secret_value_', 'other_value']], $context);
    }

    public function testMaskingConfigWithInappropriateLevel(): void
    {
        $config = ['secret_value'];
        $configMaskingProcessor = new ConfigMaskingProcessor($config, [LogLevel::INFO]);
        $record = $this->createLogRecord(['context' => ['secret_value']]);
        $result = $configMaskingProcessor($record);
        $context = $result['context'];
        $this->assertSame(['secret_value'], $context);
    }

    public function testMaskingConfigWithProcessor(): void
    {
        $config = ['secret_value'];
        $configMaskingProcessor = new ConfigMaskingProcessor($config, [LogLevel::ERROR], new PasswordMaskingProcessor());
        $record = $this->createLogRecord(['context' => ['secret_value']]);
        $expected = (new PasswordMaskingProcessor())->addMask('secret_value');
        $result = $configMaskingProcessor($record);
        $context = $result['context'];
        $this->assertSame([$expected], $context);
    }

    public function testMaskingMessageByConfig(): void
    {
        $config = ['secret_value'];
        $configMaskingProcessor = new ConfigMaskingProcessor($config);
        $record = $this->createLogRecord([
            'message' => 'Test_secret_value',
            'context' => ['secret_value'],
        ]);
        $expected = (new MaskingProcessor())->addMask('secret_value');
        $result = $configMaskingProcessor($record);
        $context = $result['context'];
        $message = $result['message'];
        $this->assertSame([$expected], $context);
        $this->assertSame("Test_{$expected}", $message);
    }

    public function testMaskingMessageByMultiArray(): void
    {
        $config = [['test' => 'secret_value']];
        $configMaskingProcessor = new ConfigMaskingProcessor($config);
        $record = $this->createLogRecord([
            'message' => 'Test_secret_value',
            'context' => ['secret_value'],
        ]);
        $expected = (new MaskingProcessor())->addMask('secret_value');
        $result = $configMaskingProcessor($record);
        $context = $result['context'];
        $message = $result['message'];
        $this->assertSame([$expected], $context);
        $this->assertSame("Test_{$expected}", $message);
    }
}