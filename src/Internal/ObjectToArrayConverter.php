<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Internal;

use DateTimeInterface;
use Maskolog\Enums\ClassType;
use ReflectionObject;

class ObjectToArrayConverter
{
    /**
     * Convert object to context array
     *
     * Returns array like:
     * [
     *   "Fully\Qualified\ClassName" => [
     *       "publicProp" => ...,
     *       "child" => [
     *           "ChildClass" => [ ... ]
     *       ]
     *   ]
     * ]
     *
     * @return array<string, mixed>
     */
    public function convert(object $object): array
    {
        return [$this->getObjectKey($object) => $this->convertObject($object)];
    }

    /**
     * Convert arbitrary value (object/array/scalar)
     *
     * @param mixed $value
     * @return mixed
     */
    protected function convertValue($value)
    {
        if (is_object($value)) {
            return [$this->getObjectKey($value) => $this->convertObject($value)];
        }

        if (is_array($value)) {
            $res = [];
            foreach ($value as $k => $v) {
                $res[$k] = $this->convertValue($v);
            }
            return $res;
        }

        return $value;
    }

    /**
     * Internal object conversion (public props / special cases)
     *
     * @return array<string, mixed>
     */
    protected function convertObject(object $object): array
    {
        if ($object instanceof DateTimeInterface) {
            return ['date' => $object->format(\DATE_ATOM)];
        }

        $public = get_object_vars($object);

        $result = [];
        foreach ($public as $name => $val) {
            $result[$name] = $this->convertValue($val);
        }

        return $result;
    }

    protected function getObjectKey(object $object): string
    {
        $ref = new ReflectionObject($object);
        if ($ref->isAnonymous()) {
            return ClassType::ANONYMOUS;
        }
        return $ref->getName();
    }
}
