<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Example;

use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Enums\UrlMaskingStatus;
use Maskolog\ExampleLogger;
use Maskolog\Exceptions\MaskedException;
use Maskolog\Exceptions\MaskingExceptionInterface;
use Maskolog\Internal\ExceptionManager;
use Maskolog\Logger;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use Maskolog\Processors\Masking\Context\StringMaskingProcessor;
use Maskolog\Processors\Masking\Context\UrlMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\Source\TestObject;
use MaskologLoggerTests\Monolog\Functional\TestHandlerConverterTrait;
use JsonException;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * Examples from README are tested here.
 */
class ExampleTest extends TestCase
{
    use TestHandlerConverterTrait;

    /**
     * @throws JsonException
     */
    public function testLoggerDefaultCreation(): void
    {
        $logger = new ExampleLogger();
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));

        $logger = $logger->withPasswordMasking();

        $maskingLogger = $logger->getMaskingLogger();
        while ($maskingLogger->getHandlers()) {
            $maskingLogger->popHandler();
        }
        $maskingLogger->pushHandler($testHandler);
        $maskingLogger->info('Text using password: {password}', ['password' => 'secret_password']);

        $mask = PasswordMaskingStatus::MASKED_PASSWORD;
        $resultMessage = "Text using password: {$mask}";

        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($resultMessage, $log->message);
    }

    /**
     * @throws JsonException
     */
    public function testCombinedMasking(): void
    {
        $logger = new ExampleLogger();
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));

        $logger = $logger->withMaskingProcessors([PasswordMaskingProcessor::class => 'password']);
        $logger = $logger->withMaskingProcessors([
            StringMaskingProcessor::class => 'token',
            UrlMaskingProcessor::class => 'url',
        ]);

        $maskingLogger = $logger->getMaskingLogger();
        while ($maskingLogger->getHandlers()) {
            $maskingLogger->popHandler();
        }
        $maskingLogger->pushHandler($testHandler);
        $maskingLogger->info('Text using sensitive data: {token}, {password}, {url}', ['password' => 'secret_password', 'token' => 'secret_token', 'url' => 'domain.ru?hash=secret_hash']);

        $maskPassword = PasswordMaskingStatus::MASKED_PASSWORD;
        $maskToken = (new StringMaskingProcessor())->addMask('secret_token');
        $maskHash = UrlMaskingStatus::REPLACEMENT;
        $resultMessage = "Text using sensitive data: {$maskToken}, {$maskPassword}, domain.ru?hash={$maskHash}";

        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($resultMessage, $log->message);
    }

    public function testMaskedException(): void
    {
        $isEnableMasking = true;
        $maskToken = (new StringMaskingProcessor())->addMask('secret');
        $expectedMessage = "Token output: {$maskToken}";
        try {
            throw (new MaskedException('Token output: {token}'))
                ->setContext(['token' => 'secret'])
                ->pushMaskingProcessor(new StringMaskingProcessor(['token']))
                ->finalize($isEnableMasking);
        } catch (MaskingExceptionInterface $e){
            $message = $e->getMessage();
        }
        $this->assertEquals($expectedMessage, $message);
    }

    public function testMaskedExceptionFromLogger(): void
    {
        $logger = new ExampleLogger();

        $maskToken = (new StringMaskingProcessor())->addMask('secret');
        $expectedMessage = "Token output: {$maskToken}";
        $exception = (new MaskedException('Token output: {token}'))->setContext(['token' => 'secret']);
        $logger = $logger->withMaskingProcessors([StringMaskingProcessor::class => 'token']);
        $manager = new ExceptionManager($logger);
        try {
            $manager->throwMaskedException($exception);
        } catch (MaskingExceptionInterface $e){
            $message = $e->getMessage();
        }
        $this->assertEquals($expectedMessage, $message);
    }

    /**
     * @throws JsonException
     */
    public function testSpecificFieldMasking(): void
    {
        $factory = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($factory);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $logger = $logger->withHandler($testHandler);
        $maskedToken = (new StringMaskingProcessor())->addMask('secret_token');

        $logger->withMaskingProcessors(
            [StringMaskingProcessor::class => ['internal' => ['token']]]
        )->info('List of tokens', [
            'internal' => ['token' => 'secret_token'],
            'external' => ['token' => 'public_token']
        ]);

        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($maskedToken, $log->context->internal->token);
        $this->assertEquals('public_token', $log->context->external->token);
    }

    public function testMaskingClassObject(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $logger = $logger->withHandler($testHandler);

        $maskCell = PasswordMaskingStatus::MASKED_PASSWORD;
        $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => [
                [TestObject::class => ['publicReadonlySecretCell', 'publicSecretCell']],
            ]]
        )->info('Masked object', [new TestObject()]);;

        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);

        $obj = ((array)(((array)current($result)->context))[0])[TestObject::class];

        $this->assertSame($maskCell, $obj->publicSecretCell);
        $this->assertSame($maskCell, $obj->publicReadonlySecretCell);
        $this->assertSame('public_data', $obj->publicCell);
    }
}