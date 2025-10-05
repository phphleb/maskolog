<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use Maskolog\Logger;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\RollbackLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\Source\ResetCloseSpyHandler;
use MaskologLoggerTests\Monolog\Functional\Source\ResetSpyProcessor;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class HandlerTest extends TestCase
{
    use TestHandlerConverterTrait;

    public function testIsHandlingHandlers(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);

        $testHandler = new TestHandler(LogLevel::ERROR);
        $logger = $logger->withHandler($testHandler);
        $errorHandler = new TestHandler(LogLevel::ERROR);
        $logger = $logger->withHandler($errorHandler);

        $this->assertFalse($logger->isHandling(LogLevel::INFO));
        $this->assertTrue($logger->isHandling(LogLevel::ERROR));

        $infoHandler = new TestHandler(LogLevel::INFO);
        $logger = $logger->withHandler($infoHandler);

        $this->assertTrue($logger->isHandling(LogLevel::INFO));
    }

    public function testIsHandlingUnmaskingHandlers(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);

        $testHandler = new TestHandler(LogLevel::ERROR);
        $logger = $logger->withHandler($testHandler);
        $errorHandler = new TestHandler(LogLevel::ERROR);
        $logger = $logger->withHandler($errorHandler);

        $this->assertFalse($logger->isHandling(LogLevel::INFO));
        $this->assertTrue($logger->isHandling(LogLevel::ERROR));

        $infoHandler = new TestHandler(LogLevel::ERROR);
        $logger = $logger->withUnmaskingHandler($infoHandler);

        $this->assertFalse($logger->isHandling(LogLevel::INFO));

        $infoHandler = new TestHandler(LogLevel::INFO);
        $logger = $logger->withUnmaskingHandler($infoHandler);

        $this->assertTrue($logger->isHandling(LogLevel::INFO));
    }

    public function testCloseSingleHandlerWithoutLog(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);

        $testHandler = new ResetCloseSpyHandler(LogLevel::ERROR);
        $logger = $logger->withHandler($testHandler);
        $logger->closeCurrent();
        $logger->close();

        $closed = $testHandler->getNumClosed();

        $this->assertEquals(0, $closed);
    }

    public function testCloseCurrentMultipleHandlers(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);

        $firstTestHandler = new ResetCloseSpyHandler(LogLevel::ERROR);
        $secondTestHandler = new ResetCloseSpyHandler(LogLevel::ERROR);
        $logger = $logger->withHandler($firstTestHandler);
        $logger = $logger->withUnmaskingHandler($secondTestHandler);
        $logger->getMaskingLogger();
        $logger->getUnmaskingLogger();
        $logger->closeCurrent();

        $firstClosed = $firstTestHandler->getNumClosed();
        $secondClosed =  $secondTestHandler->getNumClosed();

        $this->assertEquals(1, $firstClosed);
        $this->assertEquals(1, $secondClosed);
    }

    public function testCloseMultipleHandlers(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);

        $firstTestHandler = new ResetCloseSpyHandler(LogLevel::ERROR);
        $secondTestHandler = new ResetCloseSpyHandler(LogLevel::ERROR);
        $logger = $logger->withHandler($firstTestHandler);
        $logger = $logger->withUnmaskingHandler($secondTestHandler);
        $logger->getMaskingLogger();
        $logger->getUnmaskingLogger();
        $logger->close();

        $firstClosed = $firstTestHandler->getNumClosed();
        $secondClosed =  $secondTestHandler->getNumClosed();

        $this->assertEquals(1, $firstClosed);
        $this->assertEquals(1, $secondClosed);
    }

    public function testCloseCurrentAllHandlers(): void
    {
        $fabric = new RollbackLoggerFactory();
        $logger = new Logger($fabric);

        $logger = $logger->withHandler(new ResetCloseSpyHandler());
        $logger = $logger->withUnmaskingHandler(new ResetCloseSpyHandler());
        $maskingLogger = $logger->getMaskingLogger();
        $unmaskingLogger = $logger->getUnmaskingLogger();

        $maskingLogger->pushHandler(new ResetCloseSpyHandler());
        $unmaskingLogger->pushHandler(new ResetCloseSpyHandler());

        $logger->closeCurrent();

        $result = [];
        foreach(array_merge($maskingLogger->getHandlers(), $unmaskingLogger->getHandlers() ?: []) as $handler) {
            if ($handler instanceof ResetCloseSpyHandler) {
                $result[] = $handler->getNumClosed();
            }
        }

        $this->assertCount(5, $result);
        $this->assertEquals([0,1,0,1,1], $result);
    }

    public function testCloseAllHandlers(): void
    {
        $fabric = new RollbackLoggerFactory();
        $logger = new Logger($fabric);

        $logger = $logger->withHandler(new ResetCloseSpyHandler());
        $logger = $logger->withUnmaskingHandler(new ResetCloseSpyHandler());
        $maskingLogger = $logger->getMaskingLogger();
        $unmaskingLogger = $logger->getUnmaskingLogger();

        $maskingLogger->pushHandler(new ResetCloseSpyHandler());
        $unmaskingLogger->pushHandler(new ResetCloseSpyHandler());

        $logger->close();

        $result = [];
        foreach(array_merge($maskingLogger->getHandlers(), $unmaskingLogger->getHandlers() ?: []) as $handler) {
            if ($handler instanceof ResetCloseSpyHandler) {
                $result[] = $handler->getNumClosed();
            }
        }

        $this->assertCount(5, $result);
        $this->assertEquals([0,1,0,1,1], $result);
    }


    public function testResetSingleHandlerWithoutLog(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);

        $testHandler = new ResetCloseSpyHandler(LogLevel::ERROR);
        $logger = $logger->withHandler($testHandler);
        $logger->resetCurrent();
        $logger->reset();

        $reset = $testHandler->getNumReset();

        $this->assertEquals(0, $reset);
    }

    public function testResetCurrentMultipleHandlers(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);

        $firstTestHandler = new ResetCloseSpyHandler(LogLevel::ERROR);
        $secondTestHandler = new ResetCloseSpyHandler(LogLevel::ERROR);
        $logger = $logger->withHandler($firstTestHandler);
        $logger = $logger->withUnmaskingHandler($secondTestHandler);
        $logger->getMaskingLogger();
        $logger->getUnmaskingLogger();
        $logger->resetCurrent();

        $firstReset = $firstTestHandler->getNumReset();
        $secondReset =  $secondTestHandler->getNumReset();

        $this->assertEquals(1, $firstReset);
        $this->assertEquals(1, $secondReset);
    }

    public function testResetMultipleHandlers(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);

        $firstTestHandler = new ResetCloseSpyHandler(LogLevel::ERROR);
        $secondTestHandler = new ResetCloseSpyHandler(LogLevel::ERROR);
        $logger = $logger->withHandler($firstTestHandler);
        $logger = $logger->withUnmaskingHandler($secondTestHandler);
        $logger->getMaskingLogger();
        $logger->getUnmaskingLogger();
        $logger->reset();

        $firstReset = $firstTestHandler->getNumReset();
        $secondReset =  $secondTestHandler->getNumReset();

        $this->assertEquals(1, $firstReset);
        $this->assertEquals(1, $secondReset);
    }

    public function testResetCurrentAllHandlers(): void
    {
        $fabric = new RollbackLoggerFactory();
        $logger = new Logger($fabric);

        $logger = $logger->withHandler(new ResetCloseSpyHandler());
        $logger = $logger->withUnmaskingHandler(new ResetCloseSpyHandler());
        $logger = $logger->withProcessor(new ResetSpyProcessor())
            ->withMaskingProcessor(new PasswordMaskingProcessor());
        $maskingLogger = $logger->getMaskingLogger();
        $unmaskingLogger = $logger->getUnmaskingLogger();

        $maskingLogger->pushHandler(new ResetCloseSpyHandler());
        $unmaskingLogger->pushHandler(new ResetCloseSpyHandler());

        $logger->resetCurrent();

        $handlerResult = [];
        foreach(array_merge($maskingLogger->getHandlers(), $unmaskingLogger->getHandlers() ?: []) as $handler) {
            if ($handler instanceof ResetCloseSpyHandler) {
                $handlerResult[] = $handler->getNumReset();
            }
        }
        $maskingResetProcessorResult = [];
        foreach($maskingLogger->getProcessors() as $processor) {
            if ($processor instanceof ResetSpyProcessor) {
                $maskingResetProcessorResult[] = $processor->getNumReset();
            }
        }
        $unmaskingResetProcessorResult = [];
        foreach($unmaskingLogger->getProcessors() ?: [] as $processor) {
            if ($processor instanceof ResetSpyProcessor) {
                $unmaskingResetProcessorResult[] = $processor->getNumReset();
            }
        }

        $this->assertCount(5, $handlerResult);
        $this->assertEquals([0,1,0,1,1], $handlerResult);

        $this->assertCount(2, $maskingResetProcessorResult);
        $this->assertEquals([2,2], $maskingResetProcessorResult);

        $this->assertCount(2, $unmaskingResetProcessorResult);
        $this->assertEquals([2,2], $unmaskingResetProcessorResult);
    }

    public function testResetAllHandlers(): void
    {
        $fabric = new RollbackLoggerFactory();
        $logger = new Logger($fabric);

        $logger = $logger->withHandler(new ResetCloseSpyHandler());
        $logger = $logger->withUnmaskingHandler(new ResetCloseSpyHandler());
        $logger = $logger->withProcessor(new ResetSpyProcessor())
            ->withMaskingProcessor(new PasswordMaskingProcessor());
        $maskingLogger = $logger->getMaskingLogger();
        $unmaskingLogger = $logger->getUnmaskingLogger();

        $maskingLogger->pushHandler(new ResetCloseSpyHandler());
        $unmaskingLogger->pushHandler(new ResetCloseSpyHandler());

        $logger->reset();

        $handlerResult = [];
        foreach(array_merge($maskingLogger->getHandlers(), $unmaskingLogger->getHandlers() ?: []) as $handler) {
            if ($handler instanceof ResetCloseSpyHandler) {
                $handlerResult[] = $handler->getNumReset();
            }
        }
        $maskingResetProcessorResult = [];
        foreach($maskingLogger->getProcessors() as $processor) {
            if ($processor instanceof ResetSpyProcessor) {
                $maskingResetProcessorResult[] = $processor->getNumReset();
            }
        }
        $unmaskingResetProcessorResult = [];
        foreach($unmaskingLogger->getProcessors() ?: [] as $processor) {
            if ($processor instanceof ResetSpyProcessor) {
                $unmaskingResetProcessorResult[] = $processor->getNumReset();
            }
        }

        $this->assertCount(5, $handlerResult);
        $this->assertEquals([0,1,0,1,1], $handlerResult);

        $this->assertCount(2, $maskingResetProcessorResult);
        $this->assertEquals([2,3], $maskingResetProcessorResult);

        $this->assertCount(2, $unmaskingResetProcessorResult);
        $this->assertEquals([2,3], $unmaskingResetProcessorResult);
    }
}