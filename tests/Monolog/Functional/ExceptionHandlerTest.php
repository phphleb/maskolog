<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use Maskolog\Logger;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\ErrorHandlerManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\Source\DefaultException;
use MaskologLoggerTests\Monolog\Functional\Source\LocalException;
use MaskologLoggerTests\Monolog\Functional\Source\ResultException;
use MaskologLoggerTests\Monolog\Functional\Source\TestLocalExceptionHandler;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

class ExceptionHandlerTest extends TestCase
{
   public function testErrorHandler(): void
   {
       $message = 'This error will be output to the fallback handler.';
       $fabric = new ErrorHandlerManagedLoggerFactory();
       $logger = new Logger($fabric);
       $logger = $logger->withHandler(new TestHandler());
       $newLogger = clone $logger;
       $logger = $logger->withProcessor(function($record) use ($message) {
           throw new DefaultException($message);
       });
       $this->assertTrue($logger->hasExceptionHandler());
       $resultMessage = '';
       try {
           $logger->info('Checking log processing');
       } catch (ResultException $e) {
           $resultMessage = $e->getMessage();
       }
       $this->assertEquals($message, $resultMessage);

       $logger = $newLogger->withProcessor(function($record) use ($message) {
           throw new DefaultException($message);
       })->withExceptionHandler(new TestLocalExceptionHandler());
       $this->assertTrue($logger->hasExceptionHandler());
       $resultMessage = '';
       try {
           $logger->info('Checking log processing');
       } catch (LocalException $e) {
           $resultMessage = $e->getMessage();
       }
       $this->assertEquals($message, $resultMessage);

       $fabric = new SimpleTestManagedLoggerFactory();
       $logger = (new Logger($fabric))->withHandler(new TestHandler());
       $logger = $logger->withProcessor(function($record) use ($message) {
           throw new DefaultException($message);
       })->withExceptionHandler(new TestLocalExceptionHandler());
       $this->assertTrue($logger->hasExceptionHandler());
       $resultMessage = '';
       try {
           $logger->info('Checking log processing');
       } catch (LocalException $e) {
           $resultMessage = $e->getMessage();
       }
       $this->assertEquals($message, $resultMessage);
   }
}