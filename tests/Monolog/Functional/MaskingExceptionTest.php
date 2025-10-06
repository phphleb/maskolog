<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Exceptions\MaskedException;
use Maskolog\Exceptions\MaskingExceptionInterface;
use Maskolog\Logger;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\TestCase;

class MaskingExceptionTest extends TestCase
{
    use TestHandlerConverterTrait;

    public function testCreateException()
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);
        $placeholder = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $exception = (new MaskedException('Test masked message {password1}/{password2}'))
            ->setContext(['password1' => 'secret_value1', 'password2' => 'secret_value2',])
            ->pushMaskingProcessor(new PasswordMaskingProcessor(['password1']));
        $logger = $logger->withMaskingProcessor(new PasswordMaskingProcessor(['password2']));
        $resultMessage = "Test masked message {$placeholder}/{$placeholder}";
        $resultException = $logger->createMaskedException($exception);
        $this->assertEquals($resultMessage, $resultException->getMessage());
    }

    public function testThrowException()
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);
        $placeholder = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $exception = (new MaskedException('Test masked message {password1}/{password2}'))
            ->setContext(['password1' => 'secret_value1', 'password2' => 'secret_value2',])
            ->pushMaskingProcessor(new PasswordMaskingProcessor(['password1']));
        $logger = $logger->withMaskingProcessor(new PasswordMaskingProcessor(['password2']));
        $resultMessage = "Test masked message {$placeholder}/{$placeholder}";
        try {
            $logger->throwMaskedException($exception);
        } catch (MaskingExceptionInterface $e){
            $result = $e->getMessage();
        }

        $this->assertEquals($resultMessage, $result);
    }

    public function testSendToLogWithMaskExceptionData()
    {
        $fabric = new SimpleTestManagedLoggerFactory(maskingEnabled: true);
        $logger = (new Logger($fabric))->withProcessor(static function ($record) {
             return (new PsrLogMessageProcessor(removeUsedContextFields: false))($record);
         });
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);

        $placeholder = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $exception = (new MaskedException('Test masked message {password1}/{password2}'))
            ->setContext(['password1' => 'secret_value1', 'password2' => 'secret_value2'])
            ->pushMaskingProcessor(new PasswordMaskingProcessor(['password1']));
        $logger = $logger->withMaskingProcessor(new PasswordMaskingProcessor(['password2']));
        $resultMessage = "Test masked message {$placeholder}/{$placeholder}";
        $resultContext = ['password1' => $placeholder, 'password2' => $placeholder];
        $exception->sendToLog($logger);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $context = (array)current($result)?->context;
        $message = current($result)?->message;
        $this->assertEquals($resultMessage, $message);
        $this->assertEquals($resultContext, $context);
    }

    public function testSendToLogWithoutMaskExceptionData()
    {
        $fabric = new SimpleTestManagedLoggerFactory(maskingEnabled: false);
        $logger = (new Logger($fabric))->withProcessor(static function ($record) {
            return (new PsrLogMessageProcessor(removeUsedContextFields: false))($record);
        });
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);
        $originContext = ['password1' => 'secret_value1', 'password2' => 'secret_value2'];
        $exception = (new MaskedException('Test masked message {password1}/{password2}'))
            ->setContext($originContext)
            ->pushMaskingProcessor(new PasswordMaskingProcessor(['password1']));
        $exception->finalize();
        $logger = $logger->withMaskingProcessor(new PasswordMaskingProcessor(['password2']));
        $resultMessage = "Test masked message secret_value1/secret_value2";
        $exception->sendToLog($logger);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $context = (array)current($result)?->context;
        $message = current($result)?->message;
        $this->assertEquals($resultMessage, $message);
        $this->assertEquals($originContext, $context);
    }
}