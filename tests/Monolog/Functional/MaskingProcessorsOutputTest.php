<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use Maskolog\Logger;
use Maskolog\Internal\ExtraModifierProcessor;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use Maskolog\Processors\Masking\Context\StringMaskingProcessor;
use Maskolog\Processors\Masking\Context\UrlMaskingProcessor;
use Maskolog\Processors\MaskingProcessorInterface;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\GlobalMaskManagedLoggerFactory;
use PHPUnit\Framework\TestCase;
use Throwable;

class MaskingProcessorsOutputTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function testGetMaskingProcessors(): void
    {
        $fabric = new GlobalMaskManagedLoggerFactory();
        $logger = new Logger($fabric);
        $logger = $logger->withMaskingProcessors([UrlMaskingProcessor::class => []]);
        $logger = $logger->withMaskingProcessor(new UrlMaskingProcessor([]));
        $logger = $logger->withProcessor(new ExtraModifierProcessor([]));
        $processors = $logger->getMaskingProcessors();
        $logger->getMaskingLogger();
        $this->assertCount(4, $processors);
        $counter = 0;
        foreach($processors as $processor) {
            if ($processor instanceof MaskingProcessorInterface) {
                $counter++;
            }
        }
        $this->assertTrue($counter === 3);
        $logger = $logger->withMaskingProcessor(new UrlMaskingProcessor([]));
        $logger = $logger->withMaskingProcessor(new StringMaskingProcessor([]));
        $processors = $logger->getMaskingProcessors();
        $counter = 0;
        foreach($processors as $processor) {
            if ($processor instanceof MaskingProcessorInterface) {
                $counter++;
            }
        }
        $this->assertTrue($counter === 5);
        array_shift($processors);
        $current = current($processors);
        $end = end($processors);

        $this->assertTrue($current instanceof StringMaskingProcessor);
        $this->assertTrue($end instanceof PasswordMaskingProcessor);
    }
}