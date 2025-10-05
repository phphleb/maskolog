<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Unmask;

use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Logger;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\GlobalMaskManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\UnmaskManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\Source\ResultException;
use MaskologLoggerTests\Monolog\Functional\TestHandlerConverterTrait;
use JsonException;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class UnmaskingLogTest  extends TestCase
{
    use TestHandlerConverterTrait;

    /**
     * @throws JsonException
     */
    public function testUnmaskingLogger(): void
    {
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $factory = new GlobalMaskManagedLoggerFactory();
        $logger = new Logger($factory);
        $logger = $logger->withHandler($testHandler);
        $unmaskingHandler = new TestHandler();
        $unmaskingHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));

        $this->assertFalse($logger->hasUnmaskingLogger());
        $maskingLogger = $logger;
        $logger = clone $logger;

        $logger = $logger->withUnmaskingHandler($unmaskingHandler);
        $logger = $logger->withMaskingProcessors([PasswordMaskingProcessor::class => ['passwd']]);

        $password = 'secret_password';
        $mask = PasswordMaskingStatus::MASKED_PASSWORD;
        $unmask = 'visible';
        $message = 'Test mask {password}/{Pass}/{passwd} log {unmask}';
        $unmaskMessage = "Test mask {$password}/{$password}/{$password} log {$unmask}";
        $maskMessage = "Test mask {$mask}/{$mask}/{$mask} log {$unmask}";
        $logger->log(LogLevel::INFO, $message, ['password' => $password, 'passwd' => $password, 'Pass' => $password, 'unmask' => $unmask]);


        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($maskMessage, $log->message);
        $this->assertEquals($mask, $log->context->password);
        $this->assertEquals($mask, $log->context->Pass);
        $this->assertEquals($mask, $log->context->passwd);
        $this->assertEquals($unmask, $log->context->unmask);
        $this->assertTrue($logger->hasUnmaskingLogger());
        $this->assertEquals(true, $logger->isEnableMasking());

        $this->assertFalse($maskingLogger->hasUnmaskingLogger());

        $unmaskedResult = $this->convertHandler($unmaskingHandler);
        $this->assertCount(1, $unmaskedResult);
        $log = current($unmaskedResult);

        $this->assertEquals($unmaskMessage, $log->message);
        $this->assertEquals($password, $log->context->password);
        $this->assertEquals($password, $log->context->Pass);
        $this->assertEquals($password, $log->context->passwd);
        $this->assertEquals($unmask, $log->context->unmask);
    }

    public function testGlobalUnmaskingLogger(): void
    {
        $factory = new UnmaskManagedLoggerFactory();
        $logger = new Logger($factory);
        $resultMessage = '';
        try {
            $logger->log(LogLevel::INFO, __METHOD__);
        } catch (ResultException $e) {
            $resultMessage = $e->getMessage();
        }
        $this->assertEquals(__METHOD__, $resultMessage);
        $this->assertTrue($logger->hasUnmaskingLogger());
    }
}