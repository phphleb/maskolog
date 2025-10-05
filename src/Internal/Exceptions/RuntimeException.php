<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

namespace Maskolog\Internal\Exceptions;

/**
 * Implementation of errors that occurred during execution.
 */
class RuntimeException extends \LogicException implements MaskologExceptionInterface
{
}