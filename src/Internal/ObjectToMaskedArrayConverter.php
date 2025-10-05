<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Internal;

use DateTimeInterface;
use Maskolog\Processors\AbstractContextMaskingProcessor;
use ReflectionClass;

/**
 * @internal
 */
final class ObjectToMaskedArrayConverter extends ObjectToArrayConverter
{
     /** @var array<string, AbstractContextMaskingProcessor> */
     private array $processors = [];

     /**
     * Internal transformation of objects with masking by attributes
     *
     * @return mixed array|string|scalar
     */
    protected function convertObject(#[\SensitiveParameter] object $object): mixed
    {
        if ($object instanceof DateTimeInterface) {
            return ['date' => $object->format(\DATE_ATOM)];
        }
        $public = get_object_vars($object);
        if (!$public) {
            return [];
        }
        $ref = new ReflectionClass($object);
        $processors = ProcessorManager::searchMaskingProcessorsByAttributes($ref);

        $result = [];
        foreach ($public as $name => $val) {
            $result[$name] = $this->convertValue($val);
            if (isset($processors[$name])) {
                $processor = $processors[$name];
                $class = $processor;
                if (!isset($this->processors[$class])) {
                    /** @var AbstractContextMaskingProcessor $processor */
                    $this->processors[$class] = new $processor();
                }
                $result[$name] = $this->processors[$class]->addMask($result[$name]);
            }
        }

        return $result;
    }
}
