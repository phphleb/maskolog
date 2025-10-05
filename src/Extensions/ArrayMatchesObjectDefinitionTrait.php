<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Extensions;

use Maskolog\Enums\ClassType;

trait ArrayMatchesObjectDefinitionTrait
{
    /**
     * Checks whether an array matches the definition of a class
     * previously converted to an array.
     */
   protected function matchesObjectShape(#[\SensitiveParameter] mixed $value): bool
   {
       if (!is_array($value) || count($value) !== 1) {
           return false;
       }
       $key = key($value);
       if ($key === ClassType::ANONYMOUS->value) {
           return true;
       }

       return is_string($key)
           && is_array(current($value))
           && class_exists($key, false);
   }
}