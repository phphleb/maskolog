<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Processors\Masking\Context\StringMaskingProcessor;
use MaskologLoggerTests\Monolog\Units\Source\TestCollectingContextMaskingProcessor;
use PHPUnit\Framework\TestCase;

final class AbstractContextMaskingProcessorFastPathTest extends TestCase
{
    use CreateLogRecordTrait;

    public function testFastPathKeepsOriginalArrayForMatchedKey(): void
    {
        $processor = new TestCollectingContextMaskingProcessor(['token']);
        $record = $this->createLogRecord([
            'context' => [
                'token' => [
                    'token' => 'secret',
                    'other' => 'value',
                ],
            ],
        ]);

        $result = $processor($record);

        $this->assertSame(
            [
                'token' => 'secret',
                'other' => 'value',
            ],
            $processor->getReceivedValue(0)
        );
        $this->assertSame(
            'secret',
            $result['context']['token']['token']
        );
    }

    public function testFastPathMasksSimpleGlobalKeysOnNestedLevels(): void
    {
        $processor = new StringMaskingProcessor(['token', 'hash']);
        $record = $this->createLogRecord([
            'context' => [
                'token' => 'root_token',
                'nest' => [
                    'hash' => 'inner_hash',
                    'other' => [
                        'token' => 'deep_token',
                    ],
                ],
                'not_masked' => 'visible',
            ],
        ]);

        $result = $processor($record);
        $context = $result['context'];

        $this->assertSame(
            $processor->addMask('root_token'),
            $context['token']
        );
        $this->assertSame(
            $processor->addMask('inner_hash'),
            $context['nest']['hash']
        );
        $this->assertSame(
            $processor->addMask('deep_token'),
            $context['nest']['other']['token']
        );
        $this->assertSame('visible', $context['not_masked']);
    }
}
