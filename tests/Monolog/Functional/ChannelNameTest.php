<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use Maskolog\Logger;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Throwable;

class ChannelNameTest extends TestCase
{
    use TestHandlerConverterTrait;

    /**
     * @throws Throwable
     */
    public function testChangeChannel(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);

        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);

        $message = 'Test name ' . __METHOD__;
        $logger->log(LogLevel::INFO, $message);
        $result = $this->convertHandler($testHandler);

        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($message, $log->message);
        $this->assertEquals(SimpleTestManagedLoggerFactory::CHANNEL_NAME, $log->channel);

        $newName = 'new.channel';
        $logger = $logger->withName($newName);
        $message .= '2';

        $logger->log(LogLevel::INFO, $message);
        $result = $this->convertHandler($testHandler);

        $this->assertCount(2, $result);
        $log = end($result);

        $this->assertEquals($message, $log->message);
        $this->assertEquals($newName, $log->channel);
    }
}