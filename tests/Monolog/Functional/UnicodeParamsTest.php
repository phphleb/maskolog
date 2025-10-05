<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use JsonException;
use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Logger;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\GlobalMaskManagedLoggerFactory;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Throwable;

class UnicodeParamsTest extends TestCase
{
    use TestHandlerConverterTrait;

    /**
     * @throws JsonException
     */
    public function testUnicodeRusParams(): void
    {
        $fabric = new GlobalMaskManagedLoggerFactory();
        $logger = new Logger($fabric);

        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $logger = $logger->withHandler($testHandler);
        $cell = 'пароль';
        $message = "В открытом виде нельзя хранить этот пароль: ";
        $logger = $logger->withMaskingProcessors([PasswordMaskingProcessor::class => [$cell]]);
        $masked = PasswordMaskingStatus::MASKED_PASSWORD;
        $logger->log(LogLevel::INFO, "$message{{$cell}}", [$cell => 'секрет1234']);
        $result = $this->convertHandler($testHandler);

        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($message . $masked, $log->message);
    }

    /**
     * @throws JsonException
     */
    public function testUnicodeChineseParams(): void
    {
        $fabric = new GlobalMaskManagedLoggerFactory();
        $logger = new Logger($fabric);

        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $logger = $logger->withHandler($testHandler);

        $cell = '密码';
        $message = "不能以明文保存此密码: ";
        $logger = $logger->withMaskingProcessors([PasswordMaskingProcessor::class => [$cell]]);
        $masked = PasswordMaskingStatus::MASKED_PASSWORD;

        $logger->log(LogLevel::INFO, "$message{{$cell}}", [$cell => '秘密1234567890']);
        $result = $this->convertHandler($testHandler);

        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($message . $masked, $log->message);
    }

    /**
     * @throws JsonException
     */
    public function testUnicodeSpecialCharsAndEmojisParams(): void
    {
        $fabric = new GlobalMaskManagedLoggerFactory();
        $logger = new Logger($fabric);

        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $logger = $logger->withHandler($testHandler);

        $cell = '🔑';
        $message = "Do not keep these icons open for viewing: ";
        $logger = $logger->withMaskingProcessors([PasswordMaskingProcessor::class => [$cell]]);
        $masked = PasswordMaskingStatus::MASKED_PASSWORD;

        $logger->log(LogLevel::INFO, "$message{{$cell}}", [$cell => 'secret🔒😊']);
        $result = $this->convertHandler($testHandler);

        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($message . $masked, $log->message);
    }

}