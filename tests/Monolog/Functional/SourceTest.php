<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use Maskolog\Logger;
use Maskolog\SourceLocator;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use JsonException;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class SourceTest extends TestCase
{
    use TestHandlerConverterTrait;

    /**
     * @throws JsonException
     */
    public function testSourcePatch() {
       $fabric = new SimpleTestManagedLoggerFactory();
       $logger = new Logger($fabric);
       $testHandler = new TestHandler();
       $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
       $logger = $logger->withHandler($testHandler);
       $logger = $logger->withSource(...$this->delegateLocation());
       $message = 'Test source ' . __METHOD__;
       $logger->log(LogLevel::INFO, $message);
       $result = $this->convertHandler($testHandler);
       $this->assertCount(1, $result);
       $log = current($result);
       $source = self::class . ':29';

       $this->assertEquals($source, $log->extra->source);

        $logger = $logger->withSource(...$this->delegateLocation());
        $logger->log(LogLevel::INFO, $message);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(2, $result);
        $log = end($result);
        $source = self::class . ':39';

        $this->assertEquals($source, $log->extra->source);
   }

   private function delegateLocation(): array
   {
        return [...SourceLocator::get(1)];
   }
}