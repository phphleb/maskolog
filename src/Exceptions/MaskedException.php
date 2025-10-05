<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

namespace Maskolog\Exceptions;

/**
 * Implements the ability to mask error message data.
 */
class MaskedException extends \Exception implements MaskingExceptionInterface
{
   use MaskingExceptionTrait;
}