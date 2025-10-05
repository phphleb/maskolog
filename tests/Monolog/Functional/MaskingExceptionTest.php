<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Exceptions\MaskedException;
use Maskolog\Exceptions\MaskingExceptionInterface;
use Maskolog\Logger;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use PHPUnit\Framework\TestCase;

class MaskingExceptionTest extends TestCase
{
    use TestHandlerConverterTrait;

    public function testCreateException()
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);
        $placeholder = PasswordMaskingStatus::MASKED_PASSWORD;
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
        $placeholder = PasswordMaskingStatus::MASKED_PASSWORD;
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
}