<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use Maskolog\Internal\Exceptions\LogicException;
use Maskolog\Internal\ExtraModifierProcessor;
use Maskolog\Processors\Masking\Context\StringMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\Source\TestExtendedLogger;
use Monolog\Processor\ProcessorInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class LogWithoutProcessorTest extends TestCase
{
   public function testSingleProcessor(): void
   {
       $fabric = new SimpleTestManagedLoggerFactory();
       $logger = new TestExtendedLogger($fabric);
       $logger = $logger->withProcessor(new ExtraModifierProcessor([]));
       $processors = $logger->getMaskingLogger()->getProcessors();
       $search = false;
       foreach($processors as $processor) {
           if ($processor instanceof ExtraModifierProcessor) {
               $search = true;
           }
       }
       $this->assertTrue($search);

       $logger = $logger->withoutProcessorTest(ExtraModifierProcessor::class);
       $processors = $logger->getMaskingLogger()->getProcessors();
       $search = false;
       foreach($processors as $processor) {
           if ($processor instanceof ExtraModifierProcessor) {
               $search = true;
           }
       }
       $this->assertTrue(!$search);
   }

    public function testSingleProcessorFromInterface(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory(LogLevel::DEBUG, true, false);
        $logger = new TestExtendedLogger($fabric);
        $logger = $logger->withProcessor(new ExtraModifierProcessor([]));
        $logger = $logger->withProcessor(new ExtraModifierProcessor([]));
        $logger = $logger->withProcessor(new ExtraModifierProcessor([]));
        $logger = $logger->withProcessor(new ExtraModifierProcessor([]));
        $processors = $logger->getMaskingLogger()->getProcessors();
        $search = false;
        foreach($processors as $processor) {
            if ($processor instanceof ProcessorInterface) {
                $search = true;
            }
        }
        $this->assertTrue($search);

        $logger = $logger->withoutProcessorTest(ProcessorInterface::class);
        $processors = $logger->getMaskingLogger()->getProcessors();
        $search = false;
        foreach($processors as $processor) {
            if ($processor instanceof ProcessorInterface) {
                $search = true;
            }
        }
        $this->assertTrue(!$search);
    }

    public function testMaskingProcessor(): void
    {
        $this->expectException(LogicException::class);

        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new TestExtendedLogger($fabric);
        $logger->withoutProcessorTest(StringMaskingProcessor::class);
    }
}