<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use Maskolog\Internal\ExtraModifierProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\Source\TestExtendedLogger;
use MaskologLoggerTests\Monolog\Units\CreateLogRecordTrait;
use PHPUnit\Framework\TestCase;

class LogWithSingleProcessorTest extends TestCase
{
    use CreateLogRecordTrait;

   public function testSingleProcessor()
   {
       $fabric = new SimpleTestManagedLoggerFactory();
       $logger = new TestExtendedLogger($fabric);
       $logger = $logger->withProcessor(new ExtraModifierProcessor([]));
       $logger = $logger->withProcessor(new ExtraModifierProcessor([]));
       $processors = $logger->getMaskingLogger()->getProcessors();
       $counter = 0;
       foreach($processors as $processor) {
           if (is_object($processor) && get_class($processor) === ExtraModifierProcessor::class) {
               $counter++;
           }
       }
       $this->assertTrue($counter === 2);

       $logger = $logger->withSingleProcessorTest(new ExtraModifierProcessor(['tag' => 'added']));
       $processors = $logger->getMaskingLogger()->getProcessors();
       $search = 0;
       $all = 0;
       foreach($processors as $processor) {
           if (is_object($processor) && get_class($processor) === ExtraModifierProcessor::class) {
               $record = $processor($this->createLogRecord([]));
               if (isset($record['extra']['tag'])) {
                   $search++;
               }
               $all++;
           }
       }
       $this->assertTrue($search === 1);
       $this->assertTrue($all === 1);
   }
}