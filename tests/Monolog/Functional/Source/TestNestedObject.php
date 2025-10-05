<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Maskolog\Attributes\Mask;

class TestNestedObject
{
   public function __construct(
       #[Mask]
       public readonly string $publicSecretCell = 'public_secret_data',
       #[Mask]
       public ?object $maskObject = null,
       public ?object $nestedObject = null,
   )
   {
   }
}