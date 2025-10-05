<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Attributes;

use Attribute;
use Maskolog\Processors\AbstractContextMaskingProcessor;
use Maskolog\Processors\Masking\Context\MaskingProcessor;

/**
 * Attribute for masking an object field in the log context.
 *
 * ```php
 * use Maskolog\Attributes\Mask;
 * class ExampleDto {
 *    public function __construct(
 *         #[Mask]
 *         #[\SensitiveParameter]
 *         readonly public string $secret,
 *         #[Mask(PasswordMaskingProcessor::class)]
 *         #[\SensitiveParameter]
 *         readonly public string $password;
 *    ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Mask
{
    /**
     * @param class-string<AbstractContextMaskingProcessor> $processor
     */
    public function __construct(
        readonly public string $processor = MaskingProcessor::class,
    ) {
    }
}
