<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use JsonException;
use Maskolog\Logger;
use Maskolog\Processors\Masking\Context\StringMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

class SpecificFieldMaskingTest extends TestCase
{
    use TestHandlerConverterTrait;

    /**
     * @throws JsonException
     */
    public function testSpecificFieldMasking(): void
    {
        $factory = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($factory);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);
        $maskedToken = (new StringMaskingProcessor())->addMask('secret_token');

        $logger->withMaskingProcessors(
            [StringMaskingProcessor::class => ['global', 'internal' => ['token1', 'token2', 'other' => ['token1', 'token2']]]]
        )->info('List of tokens', [
            'global' => 'secret_token',
            'internal' => ['token1' => 'secret_token', 'other' => ['token1' => 'secret_token'], 'global' => 'secret_token'],
            'external' => ['token1' => 'public_token', 'other' => ['token1' => 'public_token'], 'simple' => 'public_token']
        ]);

        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($maskedToken, $log->context->global);
        $this->assertEquals($maskedToken, $log->context->internal->global);
        $this->assertEquals($maskedToken, $log->context->internal->token1);
        $this->assertEquals('public_token', $log->context->external->token1);
        $this->assertEquals($maskedToken, $log->context->internal->other->token1);
        $this->assertEquals('public_token', $log->context->external->other->token1);
    }

    /**
     * @throws JsonException
     */
    public function testSpecificSimpleFieldMasking(): void
    {
        $factory = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($factory);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);
        $maskedToken = (new StringMaskingProcessor())->addMask('secret_token');

        $logger->withMaskingProcessors(
            [StringMaskingProcessor::class => ['global', ['simple']]]
        )->info('List of tokens', [
            'global' => 'secret_token',
            'simple' => 'secret_token',
        ]);

        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);

        $this->assertEquals($maskedToken, $log->context->simple);
        $this->assertEquals($maskedToken, $log->context->global);
    }

    /**
     * @throws JsonException
     */
    public function testSpecificNumericFieldMasking(): void
    {
        $factory = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($factory);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);
        $maskedToken = (new StringMaskingProcessor())->addMask('secret_token');

        $logger->withMaskingProcessors(
            // 0 - here globally all are first, [2] - second at the initial level.
            [StringMaskingProcessor::class => [0, [2], 'level' => [1]]]
        )->info('List of tokens', [
            'secret_token',
            'public_token',
            'secret_token',
            'level' => ['secret_token', 'secret_token', 'public_token'],
        ]);

        $expected = [
            $maskedToken,
            'public_token',
            $maskedToken,
            'level' => [$maskedToken, $maskedToken, 'public_token'],
        ];

        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);
        $this->assertEquals($expected, (array)$log->context);
    }

    /**
     * @throws JsonException
     */
    public function testSpecificAllFieldMasking(): void
    {
        $factory = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($factory);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);
        $maskedToken = (new StringMaskingProcessor())->addMask('secret_token');

        $logger->withMaskingProcessors(
            [StringMaskingProcessor::class => [[], 'level' => []]]
        )->info('List of tokens', [
            ['secret_token', 'secret_token'],
            'public_token',
            'level' => ['secret_token', 'secret_token'],
        ]);

        $expected = [
            [$maskedToken, $maskedToken],
            'public_token',
            'level' => [$maskedToken, $maskedToken],
        ];

        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);
        $this->assertEquals($expected, (array)$log->context);
    }

    public function testMaskingHandlesNonStandardCharacters(): void
    {
        $factory = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($factory);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);
        $maskedToken = (new StringMaskingProcessor())->addMask('&#9875');

        $logger->withMaskingProcessors(
            [StringMaskingProcessor::class => ['smile', 200, 'level' => 400]]
        )->info('List of characters', [
            100 => 'test',
            'smile' => '&#9875',
            'level' => null,
            200 => '&#9875',
        ]);

        $expected = [
            100 => 'test',
            'smile' => $maskedToken,
            'level' => null,
            200 => $maskedToken,
        ];

        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);
        $this->assertEquals($expected, (array)$log->context);
    }

    public function testMaskingHandlesNonStandardLevel(): void
    {
        $factory = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($factory);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);
        $maskedToken = (new StringMaskingProcessor())->addMask('&#9875');

        $logger->withMaskingProcessors(
            [StringMaskingProcessor::class => ['level' => [400]]]
        )->info('List of characters', [
            'level' => [400 => '&#9875'],
        ]);

        $expected = [400 => $maskedToken];

        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $log = current($result);
        $this->assertEquals($expected, (array)$log->context->level);
    }
}