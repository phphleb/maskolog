<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Exceptions\MaskedException;
use Maskolog\Exceptions\MaskingExceptionInterface;
use Maskolog\Internal\Exceptions\LogicException;
use Maskolog\Processors\Masking\Context\StringMaskingProcessor;
use PHPUnit\Framework\TestCase;

class MaskingExceptionTraitTest extends TestCase
{
   public function testFinalizedException(): void
   {
       $isEnableMasking = true;
       $maskToken = (new StringMaskingProcessor())->addMask('secret');
       $defaultMessage = 'Token output: {token}';
       $expectedMessage = "Token output: {$maskToken}";
       $exception = (new MaskedException($defaultMessage))
           ->setContext(['token' => 'secret'])
           ->pushMaskingProcessor(new StringMaskingProcessor(['token']));
       try {
           throw $exception->finalize($isEnableMasking);
       } catch (MaskingExceptionInterface $e){
           $message = $e->getMessage();
       }
       $this->assertEquals($expectedMessage, $message);

       $exception = (new MaskedException($defaultMessage))
           ->setContext(['token' => 'secret'])
           ->pushMaskingProcessor(new StringMaskingProcessor(['token']));

       $isEnableMasking = false;
       try {
           throw $exception->finalize($isEnableMasking);
       } catch (MaskingExceptionInterface $e){
           $message = $e->getMessage();
       }
       $this->assertEquals('Token output: secret', $message);

       $this->expectException(LogicException::class);

       $finalizedException = $exception->finalize();

       $finalizedException->pushMaskingProcessor(new StringMaskingProcessor(['token']));

       $this->expectException(LogicException::class);

       $finalizedException = $exception->finalize();

       $finalizedException->setContext(['token' => 'secret']);
   }
}