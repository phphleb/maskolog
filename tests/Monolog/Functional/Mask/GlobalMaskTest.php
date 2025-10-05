<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Mask;

use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Logger;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\GlobalMaskManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\TestHandlerConverterTrait;
use JsonException;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class GlobalMaskTest  extends TestCase
{
    use TestHandlerConverterTrait;

    /**
     * @throws JsonException
     */
    public function testPasswordMask(): void
    {
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $factory = new GlobalMaskManagedLoggerFactory();
        $logger = new Logger($factory);
        /** @var TestHandler $testHandler */
        $monologLogger = $logger->getMaskingLogger();
        $monologLogger->pushHandler($testHandler);
        $password = 'secret_password';
        $mask = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $unmask = 'visible';
        $message = 'Test mask {password}/{Pass} log {unmask}';
        $resultMessage = "Test mask {$mask}/{$mask} log {$unmask}";
        $monologLogger->log(LogLevel::INFO, $message, ['password' => $password, 'Pass' => $password, 'unmask' => $unmask]);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($resultMessage, $log->message);
        $this->assertEquals($mask, $log->context->password);
        $this->assertEquals($mask, $log->context->Pass);
        $this->assertEquals($unmask, $log->context->unmask);
    }

    /**
     * @throws JsonException
     */
    public function testDuplicateMask(): void
    {
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $factory = new GlobalMaskManagedLoggerFactory();
        $logger = new Logger($factory);
        $logger = $logger->withMaskingProcessors([PasswordMaskingProcessor::class => 'passwd']);
        /** @var TestHandler $testHandler */
        $monologLogger = $logger->getMaskingLogger();
        $monologLogger->pushHandler($testHandler);
        $password = 'secret_password';
        $mask = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $unmask = 'visible';
        $message = 'Test mask {password}/{pass}/{passwd} log {unmask}';
        $resultMessage = "Test mask {$mask}/{$mask}/$mask log {$unmask}";
        $monologLogger->log(LogLevel::INFO, $message, ['password' => $password, 'pass' => $password, 'passwd' => $password, 'unmask' => $unmask]);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($resultMessage, $log->message);
        $this->assertEquals($mask, $log->context->password);
        $this->assertEquals($mask, $log->context->pass);
        $this->assertEquals($unmask, $log->context->unmask);
        $this->assertFalse($logger->hasUnmaskingLogger());
    }
}