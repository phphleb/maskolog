<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Enums\ReplaceMaskingStatus;
use Maskolog\Processors\Masking\Context\MaskingProcessor;
use Maskolog\Processors\Masking\Context\StructuredMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\Source\TestObject;
use PHPUnit\Framework\TestCase;

final class StructuredMaskingProcessorTest extends TestCase
{
    use CreateLogRecordTrait;

    private StructuredMaskingProcessor $processor;

    private string $replacement;

    protected function setUp(): void
    {
        $this->processor = new StructuredMaskingProcessor();
        $this->replacement = ReplaceMaskingStatus::REPLACEMENT->value;
    }

    public function testScalarValueReturnsReplacement(): void
    {
        $this->assertSame($this->replacement, $this->processor->addMask('secret'));
        $this->assertSame($this->replacement, $this->processor->addMask(0));
        $this->assertSame($this->replacement, $this->processor->addMask(null));
    }

    public function testFlatArrayMasksScalarsPreservesKeys(): void
    {
        $this->assertSame(
            [
                'a' => $this->replacement,
                'b' => $this->replacement,
            ],
            $this->processor->addMask(['a' => 'x', 'b' => 1])
        );
    }

    public function testNestedStructureMasksRecursively(): void
    {
        $this->assertSame(
            [
                'level1' => [
                    'level2' => [
                        'leaf' => $this->replacement,
                    ],
                    'other' => $this->replacement,
                ],
            ],
            $this->processor->addMask([
                'level1' => [
                    'level2' => [
                        'leaf' => 'secret',
                    ],
                    'other' => 42,
                ],
            ])
        );
    }

    public function testEmptyArrayReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->processor->addMask([]));
    }

    public function testListIndicesPreserved(): void
    {
        $this->assertSame(
            [$this->replacement, $this->replacement],
            $this->processor->addMask(['a', 'b'])
        );
    }

    public function testObjectShapedArrayMasksScalarsPreservesClassKey(): void
    {
        $this->assertSame(
            [
                TestObject::class => [
                    'secretCell' => $this->replacement,
                    'public' => $this->replacement,
                ],
            ],
            $this->processor->addMask([
                TestObject::class => [
                    'secretCell' => 'x',
                    'public' => 'y',
                ],
            ])
        );
    }

    public function testMatchedStructuredValuePreservesShapeViaInvoke(): void
    {
        $processor = new StructuredMaskingProcessor(['data']);
        $record = $this->createLogRecord([
            'context' => [
                'data' => [
                    'token' => 'x',
                    'meta' => [
                        'id' => 1,
                    ],
                ],
            ],
        ]);

        $result = $processor($record);

        $this->assertSame(
            [
                'data' => [
                    'token' => $this->replacement,
                    'meta' => [
                        'id' => $this->replacement,
                    ],
                ],
            ],
            $result->context
        );
    }

    public function testMaskingProcessorCollapsesStructuredValue(): void
    {
        $flatProcessor = new MaskingProcessor(['data']);
        $record = $this->createLogRecord([
            'context' => [
                'data' => [
                    'token' => 'x',
                    'meta' => [
                        'id' => 1,
                    ],
                ],
            ],
        ]);

        $result = $flatProcessor($record);

        $this->assertSame($this->replacement, $result->context['data']);
    }
}
