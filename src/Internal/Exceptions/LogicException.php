<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

namespace Maskolog\Internal\Exceptions;

/**
 * @internal
 *
 * Implementation of contextual error for logger.
 */
class LogicException extends \LogicException implements MaskologExceptionInterface
{
}