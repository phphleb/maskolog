<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Internal;

/**
 * @internal
 */
class ObjectToMaskedArrayConverterProcessor extends ObjectToArrayConverterProcessor
{
    protected static function getConverter(): ObjectToMaskedArrayConverter
    {
        return new ObjectToMaskedArrayConverter();
    }
}