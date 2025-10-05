<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

namespace Maskolog\Internal\Exceptions;

/**
 * Implementation of contextual error for logger.
 */
class InvalidArgumentException extends \InvalidArgumentException  implements MaskologExceptionInterface
{
}