<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Internal;

use Maskolog\Attributes\Mask;
use Maskolog\Internal\Exceptions\InvalidArgumentException;
use Maskolog\Internal\Exceptions\LogicException;
use Maskolog\Processors\AbstractContextMaskingProcessor;
use Maskolog\Processors\MaskingProcessorInterface;
use Monolog\Processor\ProcessorInterface;
use ReflectionClass;

/**
 * @internal
 */
class ProcessorManager
{
    /**
     * Returns the combined Monolog processors and mask processors in the order they were added.
     *
     * @param array<int, callable|ProcessorInterface|null> $processors
     * @param array<int|string, array<int|string, mixed>|string|MaskingProcessorInterface|callable> $maskingProcessors
     * @return array<int, callable|ProcessorInterface|MaskingProcessorInterface>
     */
    public static function merge(array $processors, array $maskingProcessors): array
    {
        foreach ($processors as &$processor) {
            if ($processor === null) {
                $maskProcessor = array_shift($maskingProcessors);
                if (!$maskProcessor) {
                    break;
                }
                $processor = self::convert([$maskProcessor])[0];
            }
        }
        /** @var array<int, callable|ProcessorInterface|MaskingProcessorInterface> */

        return array_values(array_filter($processors));
    }

    /**
     * Checks the validity of masking processors.
     *
     * @param array<int|string, array<int|string, mixed>|string|MaskingProcessorInterface|callable> $maskingProcessors
     * @return void
     */
    public static function check(array $maskingProcessors): void
    {
        $error = 'The masking processor type can only be callable or either a'
            . MaskingProcessorInterface::class . ' class or an array value,'
            . ' where the key is the processor class and the value is a list of fields to be masked.';
        foreach ($maskingProcessors as $key => $processor) {
            if (!((is_string($key) && (is_array($processor) || is_string($processor)))
                || (is_int($key) && $processor instanceof MaskingProcessorInterface)
                || (is_int($key) && is_callable($processor))
            )) {
                throw new InvalidArgumentException($error);
            }
            if (is_string($key) && !is_subclass_of($key, MaskingProcessorInterface::class)) {
                throw new InvalidArgumentException("The class `{$key}` being initialized must be implemented by " . MaskingProcessorInterface::class);
            }
        }
    }

    /**
     * Converts masking processors from the internal standard to an initialized object.
     *
     * @param array<int|string, array<int|string, mixed>|string|MaskingProcessorInterface|callable> $maskingProcessors
     * @return array<int, callable|MaskingProcessorInterface>
     */
    public static function convert(array $maskingProcessors): array
    {
        foreach($maskingProcessors as &$maskingProcessor) {
            if (!is_array($maskingProcessor)) {
                continue;
            }
            $maskingKey = key($maskingProcessor);
            if (!is_subclass_of((string)$maskingKey,MaskingProcessorInterface::class)) {
                continue;
            }
            $processorValue = $maskingProcessor[$maskingKey];
            if (is_string($processorValue)) {
                $processorValue = [$processorValue];
            }
            /**
             * @var callable $processorValue
             */
            $maskingProcessor = new $maskingKey($processorValue);
        }
        /** @var array<int, callable|MaskingProcessorInterface> */

        return array_values($maskingProcessors);
    }

    /**
     * Returns the modified array of processors if a match is found in it during addition.
     * The matched processor is not added, and the parameters of the matched ones are added together.
     *
     * @param array<int, MaskingProcessorInterface|array<int|string, mixed>|callable> $maskingProcessors
     * @param array<int|string, mixed> $params
     * @return array<int, MaskingProcessorInterface|array<int|string, mixed>|callable>
     */
    public static function removeDuplicates(array $maskingProcessors, string $key, #[\SensitiveParameter] array $params): array
    {
        foreach($maskingProcessors as $k => $processor) {
            if (is_array($processor)) {
                $class = key($processor);
                if ($class === $key) {
                    $originParams = current($processor);
                    /**
                     * @var array<int|string, array<int|string, mixed>> $originParams
                     * @var array<int|string, mixed> $params
                     */
                    $maskingProcessors[$k] = [$key => self::format(array_merge_recursive($originParams, $params))];
                    /** @var array<int, MaskingProcessorInterface|array<int, mixed>|callable> */

                    return $maskingProcessors;
                }
            }
        }
        return [];
    }

    /**
     * Returns matches found for masking attributes.
     *
     * @phpstan-param ReflectionClass<T> $reflectionClass
     *
     * @template T of object
     *
     * @return array<string, string>
     */
    public static function searchMaskingProcessorsByAttributes(ReflectionClass $reflectionClass): array
    {
        $result = [];

        foreach ($reflectionClass->getProperties() as $prop) {
            if (!$prop->isPublic()) {
                continue;
            }

            $attrs = $prop->getAttributes(Mask::class);

            if (empty($attrs)) {
                continue;
            }

            $propName = $prop->getName();

            foreach ($attrs as $attr) {
                $instance = $attr->newInstance();
                /** @var string $processor */
                $processor = $instance->processor;
                if (!is_subclass_of($processor, AbstractContextMaskingProcessor::class)) {
                    throw new LogicException('The processor class in the attribute must inherit from ' . AbstractContextMaskingProcessor::class);
                }
                $result[$propName] = $processor;
            }
        }

        return $result;
    }

    /**
     * Combines array values without duplicating values
     * while preserving values and sorting the sequence.
     *
     * @param array<int|string, mixed>|mixed $params
     * @return array<int|string, mixed>|mixed
     */
    private static function format(mixed $params): mixed
    {
        if (is_array($params)) {
            foreach ($params as $key => $param) {
                if (is_array($param)) {
                    $params[$key] = self::format($param);
                }
            }
            $list = [];
            $numKeyList = [];
            foreach(array_unique($params, SORT_REGULAR) as $k => $value) {
                if (is_int($k)) {
                    $numKeyList[] = $value;
                    continue;
                }
                $list[$k] = $value;
            }

            return array_merge($numKeyList, $list);
        }
       return $params;
    }
}