<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Enums;

/**
 * Contains basic patterns for masking URL values.
 * @see UrlMaskingProcessor
 */
enum UrlMaskingStatus: string
{
    case REPLACEMENT = 'REDACTED';
}
