<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use JsonException;
use Maskolog\Attributes\Mask;
use Maskolog\Enums\ClassType;
use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Logger;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use Maskolog\Processors\Masking\Context\StringMaskingProcessor;
use Maskolog\Processors\Masking\Context\MaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\Source\TestObjectWithAttributes;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class ObjectMaskingByAttributesTest  extends TestCase
{
    use TestHandlerConverterTrait;

    /**
     * @throws JsonException
     */
    public function testNamedClassObjectWithAttributes(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);

        $maskCell = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $universalProcessor = new MaskingProcessor();
        $object = new TestObjectWithAttributes();
        $message = 'Test ' . __METHOD__;
        $loggers = [$logger,  $logger->getMaskingLogger()];
        $num = 0;
        foreach ($loggers as $logger) {
            $logger->log(LogLevel::INFO, $message, [$object, 'obj' => $object]);
            $result = $this->convertHandler($testHandler);
            $this->assertCount($num + 1, $result);
            $obj = ((array)$result[$num]?->context->obj)[TestObjectWithAttributes::class];

            $this->assertSame($maskCell, $obj->publicSecretPassword);
            $this->assertSame($universalProcessor->addMask('public_readonly_secret_data'), $obj->publicReadonlySecretCell);
            $this->assertSame('public_data', $obj->publicCell);

            $obj = ((array)((array)$result[$num]?->context)[0])[TestObjectWithAttributes::class];

            $this->assertSame($maskCell, $obj->publicSecretPassword);
            $this->assertSame($universalProcessor->addMask('public_readonly_secret_data'), $obj->publicReadonlySecretCell);
            $this->assertSame('public_data', $obj->publicCell);

            $num++;
        }
    }

    /**
     * @throws JsonException
     */
    public function testClassObjectWithAttributes(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);

        $maskCell = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $universalProcessor = new MaskingProcessor();
        $object = new class {
            public function __construct(
                #[Mask]
                public readonly string $publicReadonlySecretCell = 'public_readonly_secret_data',
                #[Mask(PasswordMaskingProcessor::class)]
                public string $publicSecretPassword = 'public_secret_data',
                public string $publicCell = 'public_data',
            ){}
        };
        $message = 'Test ' . __METHOD__;
        $loggers = [$logger,  $logger->getMaskingLogger()];
        $num = 0;
        foreach ($loggers as $logger) {
            $logger->log(LogLevel::INFO, $message, [$object, 'obj' => $object]);
            $result = $this->convertHandler($testHandler);
            $this->assertCount($num + 1, $result);
            $obj = ((array)$result[$num]?->context->obj)[ClassType::ANONYMOUS->value];

            $this->assertSame($maskCell, $obj->publicSecretPassword);
            $this->assertSame($universalProcessor->addMask('public_readonly_secret_data'), $obj->publicReadonlySecretCell);
            $this->assertSame('public_data', $obj->publicCell);

            $obj = ((array)((array)$result[$num]?->context)[0])[ClassType::ANONYMOUS->value];

            $this->assertSame($maskCell, $obj->publicSecretPassword);
            $this->assertSame($universalProcessor->addMask('public_readonly_secret_data'), $obj->publicReadonlySecretCell);
            $this->assertSame('public_data', $obj->publicCell);

            $num++;
        }
    }
}
