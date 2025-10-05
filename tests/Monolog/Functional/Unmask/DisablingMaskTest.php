<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Unmask;

use Maskolog\Logger;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\GlobalMaskManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\TestHandlerConverterTrait;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class DisablingMaskTest extends TestCase
{
    use TestHandlerConverterTrait;

    public function testDisablingMaskInMonologLogger(): void
    {
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $factory = new GlobalMaskManagedLoggerFactory(maskingEnabled: false);
        $logger = new Logger($factory);
        /** @var TestHandler $testHandler */
        $monologLogger = $logger->getMaskingLogger();
        $monologLogger->pushHandler($testHandler);
        $password = 'secret_password';
        $unmask = 'visible';
        $message = 'Test mask {password}/{Pass} log {unmask}';
        $resultMessage = "Test mask {$password}/{$password} log {$unmask}";
        $monologLogger->log(LogLevel::INFO, $message, ['password' => $password, 'Pass' => $password, 'unmask' => $unmask]);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($resultMessage, $log->message);
        $this->assertEquals($password, $log->context->password);
        $this->assertEquals($password, $log->context->Pass);
        $this->assertEquals($unmask, $log->context->unmask);
        $this->assertEquals(false, $logger->isEnableMasking());

        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $monologLogger = $logger->withUnmaskingHandler($testHandler)->getUnmaskingLogger();

        $monologLogger->log(LogLevel::INFO, $message, ['password' => $password, 'Pass' => $password, 'unmask' => $unmask]);

        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($resultMessage, $log->message);
        $this->assertEquals($password, $log->context->password);
        $this->assertEquals($password, $log->context->Pass);
        $this->assertEquals($unmask, $log->context->unmask);
    }
}