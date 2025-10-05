<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Maskolog\Attributes\Mask;
use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;

class TestObjectWithAttributes
{
   public function __construct(
       #[Mask]
       public readonly string $publicReadonlySecretCell = 'public_readonly_secret_data',
       #[Mask(PasswordMaskingProcessor::class)]
       public string $publicSecretPassword = 'public_secret_data',
       public string $publicCell = 'public_data',
   )
   {
   }
}