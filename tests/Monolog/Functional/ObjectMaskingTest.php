<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use Maskolog\Enums\ClassType;
use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Logger;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\Source\TestObject;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class ObjectMaskingTest  extends TestCase
{
    use TestHandlerConverterTrait;

    public function testSingleStdClassObject(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $logger = $logger->withHandler($testHandler);

        $maskCell = PasswordMaskingStatus::MASKED_PASSWORD;
        $logger = $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => ['secretCell', 'readonlyCell', 'lowercaseCell']]
        );
        $object = new class {
            public string $unmaskCell = 'unmask_cell';
            public string $secretCell = 'secret_cell';
            public string $lowercasecell = 'secret_cell';
            public string $readonlyCell = 'secret_cell';
        };
        $message = 'Test ' . __METHOD__;
        $logger->log(LogLevel::INFO, $message, ['obj' => $object]);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $obj = ((array)current($result)->context->obj)[ClassType::ANONYMOUS];

        $this->assertSame($maskCell, $obj->secretCell);
        $this->assertSame($maskCell, $obj->readonlyCell);
        $this->assertSame($maskCell, $obj->lowercasecell);
        $this->assertSame('unmask_cell', $obj->unmaskCell);
    }

    public function testMultiLevelStdClassObject(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $logger = $logger->withHandler($testHandler);

        $maskCell = PasswordMaskingStatus::MASKED_PASSWORD;
        $logger = $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => ['secretCell', 'readonlyCell', 'lowercaseCell']]
        );
        $object = new class {
            public string $unmaskCell = 'unmask_cell';
            public string $lowercasecell = 'secret_cell';
            public string $secretCell = 'secret_cell';
            public string $readonlyCell = 'secret_cell';

        };
        $parentObject = new class {
            public ?object $readonlyCell = null;
            public ?object $testCell = null;

            public function __construct(
                ?object $readonlyCell = null,
                ?object $testCell = null
            ){
                $this->testCell = $testCell;
                $this->readonlyCell = $readonlyCell;
            }
        };

        $message = 'Test ' . __METHOD__;
        $logger->log(LogLevel::INFO, $message, ['obj' => new $parentObject($object, clone $object)]);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $obj = ((array)current($result)->context->obj)[ClassType::ANONYMOUS];

        $testCell = ((array)$obj->testCell)[ClassType::ANONYMOUS];

        $this->assertSame($maskCell, $testCell->secretCell);
        $this->assertSame($maskCell, $testCell->readonlyCell);
        $this->assertSame($maskCell, $testCell->lowercasecell);
        $this->assertSame('unmask_cell', $testCell->unmaskCell);
    }

    public function testMaskAllInStdClassObject(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $logger = $logger->withHandler($testHandler);

        $maskCell = PasswordMaskingStatus::MASKED_PASSWORD;
        $logger = $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => []]
        );
        $object = new class {
            public string $maskCell = 'mask_cell';
            public string $secretCell = 'secret_cell';
            public string $lowercasecell = 'secret_cell';
            public string $readonlyCell = 'secret_cell';
        };
        $parentObject = new class {
            public ?object $readonlyCell = null;
            public ?object $testCell = null;

            public function __construct(
                ?object $readonlyCell = null,
                ?object $testCell = null
            ){
                $this->testCell = $testCell;
                $this->readonlyCell = $readonlyCell;
            }
        };

        $message = 'Test ' . __METHOD__;
        $logger->log(LogLevel::INFO, $message, ['obj' => new $parentObject($object, clone $object)]);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $obj = ((array)current($result)->context->obj)[ClassType::ANONYMOUS];

        $testCell = ((array)$obj->testCell)[ClassType::ANONYMOUS];
        $readonlyCell = ((array)$obj->readonlyCell)[ClassType::ANONYMOUS];

        $this->assertSame($maskCell, $testCell->secretCell);
        $this->assertSame($maskCell, $testCell->readonlyCell);
        $this->assertSame($maskCell, $testCell->lowercasecell);
        $this->assertSame($maskCell, $testCell->maskCell);
        $this->assertSame($maskCell, $readonlyCell->maskCell);
        $this->assertSame($maskCell, $readonlyCell->readonlyCell);
    }

    public function testIndividualMaskCellInStdClassObject(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $logger = $logger->withHandler($testHandler);

        $maskCell = PasswordMaskingStatus::MASKED_PASSWORD;
        $logger = $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => [
                'secretCell',
                'obj' => [
                    ClassType::ANONYMOUS => [
                        'level' => [ClassType::ANONYMOUS => [
                            'sublevel' => [ClassType::ANONYMOUS => ['innerCell']]]
                        ]
                    ]
                ]
            ]]
        );
        $levelObject = new class {
            public ?object $level = null;
            public string $secretCell = 'secret_cell';
            public string $unmaskCell = 'unmask_cell';

            public function __construct(
                ?object $level = null,
                string $secretCell = 'secret_cell',
                string $unmaskCell = 'unmask_cell'
            ){
                $this->unmaskCell = $unmaskCell;
                $this->secretCell = $secretCell;
                $this->level = $level;
            }
        };
        $sublevelObject = new class {
            public ?object $sublevel = null;
            public string $secretCell = 'secret_cell';
            public string $unmaskCell = 'unmask_cell';

            public function __construct(
                ?object $sublevel = null,
                string $secretCell = 'secret_cell',
                string $unmaskCell = 'unmask_cell'
            ){
                $this->unmaskCell = $unmaskCell;
                $this->secretCell = $secretCell;
                $this->sublevel = $sublevel;
            }
        };
        $object = new class {
            public string $innerCell = 'secret_cell';
            public string $secretCell = 'secret_cell';
            public string $unmaskCell = 'unmask_cell';

            public function __construct(
                string $innerCell = 'secret_cell',
                string $secretCell = 'secret_cell',
                string $unmaskCell = 'unmask_cell'
            ){
                $this->unmaskCell = $unmaskCell;
                $this->secretCell = $secretCell;
                $this->innerCell = $innerCell;
            }
        };

        $message = 'Test ' . __METHOD__;
        $logger->log(LogLevel::INFO, $message, ['obj' => new $levelObject(new $sublevelObject(new $object))]);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $obj = ((array)current($result)->context->obj)[ClassType::ANONYMOUS];

        $level = ((array)$obj->level)[ClassType::ANONYMOUS];
        $sublevel = ((array)$level->sublevel)[ClassType::ANONYMOUS];

        $this->assertSame($maskCell, $level->secretCell);
        $this->assertSame($maskCell, $sublevel->secretCell);
        $this->assertSame('unmask_cell', $level->unmaskCell);
        $this->assertSame('unmask_cell', $sublevel->unmaskCell);
        $this->assertSame($maskCell, $sublevel->innerCell);
    }

    public function testNamedClassObject(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory();
        $logger = new Logger($fabric);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $logger = $logger->withHandler($testHandler);

        $maskCell = PasswordMaskingStatus::MASKED_PASSWORD;
        $logger = $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => [
                [TestObject::class => ['publicReadonlySecretCell']],
                'obj' => [TestObject::class => ['publicReadonlySecretCell']],
                'publicSecretCell'
            ]]
        );
        $object = new TestObject();
        $message = 'Test ' . __METHOD__;
        $loggers = [$logger, $logger->getMaskingLogger()];
        $num = 0;
        foreach ($loggers as $logger) {
            $logger->log(LogLevel::INFO, $message, [$object, 'obj' => $object]);
            $result = $this->convertHandler($testHandler);
            $this->assertCount($num + 1, $result);
            $obj = ((array)$result[$num]->context->obj)[TestObject::class];

            $this->assertSame($maskCell, $obj->publicSecretCell);
            $this->assertSame($maskCell, $obj->publicReadonlySecretCell);
            $this->assertSame('public_data', $obj->publicCell);

            $obj = ((array)((array)$result[$num]->context)[0])[TestObject::class];

            $this->assertSame($maskCell, $obj->publicSecretCell);
            $this->assertSame($maskCell, $obj->publicReadonlySecretCell);
            $this->assertSame('public_data', $obj->publicCell);

            $num++;
        }
    }

    public function testNamedClassObjectWithoutMaskedObject(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory(LogLevel::DEBUG, true, false);
        $logger = new Logger($fabric);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $logger = $logger->withHandler($testHandler);

        $logger = $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => [
                [TestObject::class => ['publicReadonlySecretCell']],
                'obj' => [TestObject::class => ['publicReadonlySecretCell']],
                'publicSecretCell'
            ]]
        );
        $object = new TestObject();
        $message = 'Test ' . __METHOD__;
        $loggers = [$logger, $logger->getMaskingLogger()];
        $num = 0;
        foreach ($loggers as $logger) {
            $logger->log(LogLevel::INFO, $message, [$object, 'obj' => $object]);
            $result = $this->convertHandler($testHandler);
            $this->assertCount($num + 1, $result);
            $obj = $result[$num]->context->obj;

            $this->assertSame('public_secret_data', $obj->publicSecretCell);
            $this->assertSame('public_readonly_secret_data', $obj->publicReadonlySecretCell);
            $this->assertSame('public_data', $obj->publicCell);

            $obj = (((array)$result[$num]->context))[0];

            $this->assertSame('public_secret_data', $obj->publicSecretCell);
            $this->assertSame('public_readonly_secret_data', $obj->publicReadonlySecretCell);
            $this->assertSame('public_data', $obj->publicCell);

            $num++;
        }
    }

    public function testNamedClassObjectTargetMaskedObject(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory(LogLevel::DEBUG, true, false);
        $logger = new Logger($fabric);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, false, true));
        $logger = $logger->withHandler($testHandler);

        $logger = $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => [
                'publicSecretCell', 'level' => ['sublevel']
            ]]
        );
        $object = new TestObject();
        $message = 'Test ' . __METHOD__;
        $loggers = [$logger, $logger->getMaskingLogger()];
        $num = 0;
        foreach ($loggers as $logger) {
            $logger->log(LogLevel::INFO, $message, [
                'publicSecretCell' => $object,
                'level' => ['sublevel' => $object, 'publicSecretCell' => $object, 'other' => $object],
            ]);
            $result = $this->convertHandler($testHandler);
            $this->assertCount($num + 1, $result);
            $obj = $result[$num]->context->publicSecretCell;

            $error = PasswordMaskingStatus::detailedFormat($object, PasswordMaskingStatus::INVALID_TYPE_PASSWORD);

            $this->assertSame($error, $obj);

            $level = $result[$num]->context->level;

            $this->assertSame($error, $level->sublevel);
            $this->assertSame($error, $level->publicSecretCell);
            $this->assertSame('public_readonly_secret_data', $level->other->publicReadonlySecretCell);

            $num++;
        }
    }
}
