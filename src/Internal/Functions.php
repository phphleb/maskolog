<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Internal;

use Maskolog\Enums\ClassType;

class Functions
{
    /**
     * Similar to the get_debug_type() function.
     *
     * @param mixed $value
     * @return string
     */
    public static function getDebugType($value): string
    {
        if (is_object($value)) {
            $class = get_class($value);
            if (mb_strpos($class, ClassType::ANONYMOUS) === false) {
                return $class;
            }
            return ClassType::ANONYMOUS;
        }
        if (is_null($value)) return 'null';
        if (is_bool($value)) return 'bool';
        if (is_int($value)) return 'int';
        if (is_float($value)) return 'float';
        if (is_string($value)) return 'string';
        if (is_array($value)) return 'array';
        if (is_resource($value)) return get_resource_type($value) ?: 'resource';

        return gettype($value);
    }
}