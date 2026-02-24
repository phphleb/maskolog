<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Combine;

use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Logger;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\CombineManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\TestHandlerConverterTrait;
use JsonException;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

/**
 * Examples from README are tested here.
 */
class CombineHandlersTest extends TestCase
{
    use TestHandlerConverterTrait;

    /**
     * @throws JsonException
     */
    public function testLoggerHandlers(): void
    {
        $unmaskTestHandler = new TestHandler();
        $unmaskTestHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));

        $fabric = new CombineManagedLoggerFactory($testHandler, $unmaskTestHandler);
        $logger = new Logger($fabric);

        $logger->info('Text using password: {password}', ['password' => 'secret_password']);

        $handlers = $logger->getMaskingLogger()->getHandlers();

        $unmaskHandlers = $logger->getUnmaskingLogger()->getHandlers();

        $this->assertCount(1, $handlers);

        $this->assertCount(1, $unmaskHandlers);

        $mask = PasswordMaskingStatus::MASKED_PASSWORD;
        $resultMaskMessage = "Text using password: {$mask}";
        $resultUnmaskMessage = "Text using password: secret_password";

        $unmaskResult = $this->convertHandler($testHandler);
        $this->assertCount(1, $unmaskResult);
        $log = current($unmaskResult);

        $this->assertEquals($resultMaskMessage, $log->message);

        $maskResult = $this->convertHandler($unmaskTestHandler);
        $this->assertCount(1, $maskResult);
        $log = current($maskResult);

        $this->assertEquals($resultUnmaskMessage, $log->message);
    }

    /**
     * @throws JsonException
     */
    public function testLoggerHandlers2(): void
    {
        $unmaskTestHandler = new TestHandler();
        $unmaskTestHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));

        $fabric = new CombineManagedLoggerFactory($testHandler, $unmaskTestHandler);
        $logger = new Logger($fabric);

        $logger->info('Text using password: {password}', ['password' => 'secret_password']);

        $unmaskHandlers = $logger->getUnmaskingLogger()->getHandlers();

        $handlers = $logger->getMaskingLogger()->getHandlers();

        $this->assertCount(1, $unmaskHandlers);

        $this->assertCount(1, $handlers);

        $mask = PasswordMaskingStatus::MASKED_PASSWORD;
        $resultMaskMessage = "Text using password: {$mask}";
        $resultUnmaskMessage = "Text using password: secret_password";

        $unmaskResult = $this->convertHandler($testHandler);
        $this->assertCount(1, $unmaskResult);
        $log = current($unmaskResult);

        $this->assertEquals($resultMaskMessage, $log->message);

        $maskResult = $this->convertHandler($unmaskTestHandler);
        $this->assertCount(1, $maskResult);
        $log = current($maskResult);

        $this->assertEquals($resultUnmaskMessage, $log->message);
    }
}