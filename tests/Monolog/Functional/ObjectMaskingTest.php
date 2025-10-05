<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional;

use Maskolog\Enums\ClassType;
use Maskolog\Enums\PasswordMaskingStatus;
use Maskolog\Logger;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use Maskolog\Processors\Masking\Context\StringMaskingProcessor;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\SimpleTestManagedLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\Source\TestObject;
use MaskologLoggerTests\Monolog\Functional\Source\TestObjectWithAttributes;
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
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);

        $maskCell = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $logger = $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => ['secretCell', 'readonlyCell', 'lowercaseCell']]
        );
        $object = new class {
            public function __construct(
                public string $unmaskCell = 'unmask_cell',
                public string $secretCell = 'secret_cell',
                public string $lowercasecell = 'secret_cell',
                readonly public string $readonlyCell = 'secret_cell'
            ){}
        };
        $message = 'Test ' . __METHOD__;
        $logger->log(LogLevel::INFO, $message, ['obj' => $object]);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $obj = ((array)current($result)?->context->obj)[ClassType::ANONYMOUS->value];

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
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);

        $maskCell = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $logger = $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => ['secretCell', 'readonlyCell', 'lowercaseCell']]
        );
        $object = new class {
            public function __construct(
                public string $unmaskCell = 'unmask_cell',
                public string $lowercasecell = 'secret_cell',
                public string $secretCell = 'secret_cell',
                readonly public string $readonlyCell = 'secret_cell'
            ){}
        };
        $parentObject = new class {
            public function __construct(
                readonly public ?object $readonlyCell = null,
                readonly public ?object $testCell = null,
            ){}
        };

        $message = 'Test ' . __METHOD__;
        $logger->log(LogLevel::INFO, $message, ['obj' => new $parentObject($object, clone $object)]);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $obj = ((array)current($result)?->context->obj)[ClassType::ANONYMOUS->value];

        $testCell = ((array)$obj->testCell)[ClassType::ANONYMOUS->value];

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
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);

        $maskCell = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $logger = $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => []]
        );
        $object = new class {
            public function __construct(
                public string $maskCell = 'mask_cell',
                public string $lowercasecell = 'secret_cell',
                public string $secretCell = 'secret_cell',
                readonly public string $readonlyCell = 'secret_cell'
            ){}
        };
        $parentObject = new class {
            public function __construct(
                public ?object $readonlyCell = null,
                readonly public ?object $testCell = null,
            ){}
        };

        $message = 'Test ' . __METHOD__;
        $logger->log(LogLevel::INFO, $message, ['obj' => new $parentObject($object, clone $object)]);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $obj = ((array)current($result)?->context->obj)[ClassType::ANONYMOUS->value];

        $testCell = ((array)$obj->testCell)[ClassType::ANONYMOUS->value];
        $readonlyCell = ((array)$obj->readonlyCell)[ClassType::ANONYMOUS->value];

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
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);

        $maskCell = PasswordMaskingStatus::MASKED_PASSWORD->value;
        $logger = $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => [
                'secretCell',
                'obj' => [
                    ClassType::ANONYMOUS->value => [
                        'level' => [ClassType::ANONYMOUS->value => [
                            'sublevel' => [ClassType::ANONYMOUS->value => ['innerCell']]]
                        ]
                    ]
                ]
            ]]
        );
        $levelObject = new class {
            public function __construct(
                public ?object $level = null,
                public string $secretCell = 'secret_cell',
                public string $unmaskCell = 'unmask_cell',
            ){}
        };
        $sublevelObject = new class {
            public function __construct(
                public ?object $sublevel = null,
                public string $secretCell = 'secret_cell',
                public string $unmaskCell = 'unmask_cell',
            ){}
        };
        $object = new class {
            public function __construct(
                public string $innerCell = 'secret_cell',
                public string $secretCell = 'secret_cell',
                public string $unmaskCell = 'unmask_cell',
            ){}
        };

        $message = 'Test ' . __METHOD__;
        $logger->log(LogLevel::INFO, $message, ['obj' => new $levelObject(new $sublevelObject(new $object))]);
        $result = $this->convertHandler($testHandler);
        $this->assertCount(1, $result);
        $obj = ((array)current($result)?->context->obj)[ClassType::ANONYMOUS->value];

        $level = ((array)$obj->level)[ClassType::ANONYMOUS->value];
        $sublevel = ((array)$level->sublevel)[ClassType::ANONYMOUS->value];

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
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);

        $maskCell = PasswordMaskingStatus::MASKED_PASSWORD->value;
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
            $obj = ((array)$result[$num]?->context->obj)[TestObject::class];

            $this->assertSame($maskCell, $obj->publicSecretCell);
            $this->assertSame($maskCell, $obj->publicReadonlySecretCell);
            $this->assertSame('public_data', $obj->publicCell);

            $obj = ((array)((array)$result[$num]?->context)[0])[TestObject::class];

            $this->assertSame($maskCell, $obj->publicSecretCell);
            $this->assertSame($maskCell, $obj->publicReadonlySecretCell);
            $this->assertSame('public_data', $obj->publicCell);

            $num++;
        }
    }

    public function testNamedClassObjectWithoutMaskedObject(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory(maskObjects: false);
        $logger = new Logger($fabric);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
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
            $obj = $result[$num]?->context->obj;

            $this->assertSame('public_secret_data', $obj->publicSecretCell);
            $this->assertSame('public_readonly_secret_data', $obj->publicReadonlySecretCell);
            $this->assertSame('public_data', $obj->publicCell);

            $obj = (((array)$result[$num]?->context))[0];

            $this->assertSame('public_secret_data', $obj->publicSecretCell);
            $this->assertSame('public_readonly_secret_data', $obj->publicReadonlySecretCell);
            $this->assertSame('public_data', $obj->publicCell);

            $num++;
        }
    }

    public function testNamedClassObjectTargetMaskedObject(): void
    {
        $fabric = new SimpleTestManagedLoggerFactory(maskObjects: false);
        $logger = new Logger($fabric);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
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
            $obj = $result[$num]?->context->publicSecretCell;

            $error = PasswordMaskingStatus::INVALID_TYPE_PASSWORD->detailedFormat($object);

            $this->assertSame($error, $obj);

            $level = $result[$num]?->context->level;

            $this->assertSame($error, $level->sublevel);
            $this->assertSame($error, $level->publicSecretCell);
            $this->assertSame('public_readonly_secret_data', $level->other->publicReadonlySecretCell);

            $num++;
        }
    }
}
