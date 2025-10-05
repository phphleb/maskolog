<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Serialize;

use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Logger;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SerializedManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SerializedMaskManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\TestHandlerConverterTrait;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class SerializeTest  extends TestCase
{
    use TestHandlerConverterTrait;

    public function testSerializedLogger(): void
    {
        $factory = new SerializedManagedLoggerFactory();
        $logger = new Logger($factory);
        $serializedLogger = serialize($logger);
        $logger = unserialize($serializedLogger);

        $this->assertTrue($logger instanceof Logger);
    }

    public function testMaskAndUnmaskingSerializeLogger(): void
    {
        $factory = new SerializedMaskManagedLoggerFactory();
        $logger = new Logger($factory);
        $unmaskingHandler = new TestHandler();
        $unmaskingHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $maskHandler = clone $unmaskingHandler;
        $logger = $logger->withMaskingProcessors([PasswordMaskingProcessor::class => ['passwd']]);

        $serializedLogger = serialize($logger);
        /** @var Logger $logger */
        $logger = unserialize($serializedLogger);

        $logger = $logger->withUnmaskingHandler($unmaskingHandler);
        $logger = $logger->withHandler($maskHandler);

        $password = 'secret_password';
        $mask = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $unmask = 'visible';
        $message = 'Test mask {password}/{Pass}/{passwd} log {unmask}';
        $unmaskMessage = "Test mask {$password}/{$password}/{$password} log {$unmask}";
        $maskMessage = "Test mask {$mask}/{$mask}/{$mask} log {$unmask}";

        $logger->log(LogLevel::INFO, $message, ['password' => $password, 'passwd' => $password, 'Pass' => $password, 'unmask' => $unmask]);


        $result = $this->convertHandler($maskHandler);
        $this->assertTrue($logger->hasUnmaskingLogger());
        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($maskMessage, $log->message);
        $this->assertEquals($mask, $log->context->password);
        $this->assertEquals($mask, $log->context->Pass);
        $this->assertEquals($mask, $log->context->passwd);
        $this->assertEquals($unmask, $log->context->unmask);

        $unmaskedResult = $this->convertHandler($unmaskingHandler);
        $this->assertCount(1, $unmaskedResult);
        $log = current($unmaskedResult);

        $this->assertEquals($unmaskMessage, $log->message);
        $this->assertEquals($password, $log->context->password);
        $this->assertEquals($password, $log->context->Pass);
        $this->assertEquals($password, $log->context->passwd);
        $this->assertEquals($unmask, $log->context->unmask);
        $this->assertEquals(SerializedMaskManagedLoggerFactory::CHANNEL_NAME, $log->channel);
    }
}