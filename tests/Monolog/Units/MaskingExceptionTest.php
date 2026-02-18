<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Exceptions\MaskedException;
use Maskolog\Exceptions\MaskingExceptionInterface;
use Maskolog\Internal\ExceptionManager;
use Maskolog\Logger;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use Maskolog\Processors\Masking\Context\StringMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use PHPUnit\Framework\TestCase;

class MaskingExceptionTest extends TestCase
{
    public function testExceptionMessageWithoutMasking() {
        $message = 'Test message';
        $exception = new MaskedException($message);
        try {
            throw $exception;
        } catch (MaskedException $e) {
            $result = $e->getMessage();
        }
        $this->assertSame($result, $message);
    }

    public function testExceptionFinalizeWithoutMasking() {
        $message = 'Test message';
        $exception = new MaskedException($message);
        $exception->finalize();
        try {
            throw $exception;
        } catch (MaskedException $e) {
            $result = $e->getMessage();
        }
        $this->assertSame($result, $message);
    }

    public function testExceptionWithExternalMaskingProcessor() {
        $message = 'Test {password}';
        $maskedMessage =  'Test ' . PasswordMaskingStatus::MASKED_PASSWORD->value;
        $exception = (new MaskedException($message))
            ->setContext(['password' => 'secret_password'])
            ->pushMaskingProcessor(new PasswordMaskingProcessor(['password']))
            ->finalize();
        try {
            throw $exception;
        } catch (MaskedException $e) {
            $result = $e->getMessage();
        }
        $this->assertSame($maskedMessage, $result);
    }

    public function testExceptionWithMaskingProcessors() {
        $message = 'Test {password}/{pass}/{unmask}';
        $placeholder = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $maskedMessage =  "Test {$placeholder}/{$placeholder}/test";
        $exception = (new MaskedException($message))
            ->setContext([
                'password' => 'secret_password_1',
                'pass' => 'secret_password_2',
                'unmask' => 'test'
            ])
            ->pushMaskingProcessor(new PasswordMaskingProcessor(['password', 'pass']))
            ->finalize();
        try {
            throw $exception;
        } catch (MaskedException $e) {
            $result = $e->getMessage();
        }
        $this->assertSame($maskedMessage, $result);
    }

    public function testMaskedExceptionFromLogger(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);

        $maskToken = (new StringMaskingProcessor())->addMask('secret');
        $expectedMessage = "Token output: {$maskToken}";
        $exception = (new MaskedException('Token output: {token}'))->setContext(['token' => 'secret']);
        $logger = $logger->withMaskingProcessors([StringMaskingProcessor::class => 'token']);
        $manager = new ExceptionManager($logger);
        try {
            $manager->throwMaskedException($exception);
        } catch (MaskingExceptionInterface $e){
            $message = $e->getMessage();
        }
        $this->assertEquals($expectedMessage, $message);
    }
}