<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use Maskolog\Logger;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use JsonException;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Throwable;

class BaseLevelLoggerTest extends TestCase
{
    use TestHandlerConverterTrait;

    /**
     * @throws Throwable
     */
    public function testSingleInfoLog(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);

        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);

        $message = 'Test single INFO log ' . __METHOD__;
        $logger->log(LogLevel::INFO, $message);
        $result = $this->convertHandler($testHandler);

        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($message, $log->message);
        $this->assertEquals(SimpleTestManagedLoggerFactory::CHANNEL_NAME, $log->channel);
        $this->assertEquals('INFO', $log->level_name);
    }

    /**
     * @throws JsonException
     */
    public function testMultipleLog(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);

        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);

        $levels = [
            LogLevel::DEBUG,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
            LogLevel::ERROR,
            LogLevel::CRITICAL,
            LogLevel::ALERT,
            LogLevel::EMERGENCY,
        ];
        $count = 0;
        foreach ($levels as $level) {
            $message = "Test {$level} log " . __METHOD__;
            $logger->log($level, $message);

            $result = $this->convertHandler($testHandler);
            $log = $result[$count];

            $this->assertEquals($message, $log->message);
            $this->assertEquals(SimpleTestManagedLoggerFactory::CHANNEL_NAME, $log->channel);
            $this->assertEquals(strtoupper($level), $log->level_name);
            $count++;
        }
    }
}