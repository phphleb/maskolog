<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

class TestObject
{
   public function __construct(
       public readonly string $publicReadonlySecretCell = 'public_readonly_secret_data',
       public string $publicSecretCell = 'public_secret_data',
       public string $publicCell = 'public_data',
       protected string $protectedCell = 'protected_data',
   )
   {
   }

   /** @return array<string, string> */
   public function toArray(): array
   {
       return [
           'publicReadonlySecretCell' => 'public_readonly_secret_data',
           'publicSecretCell' => 'public_secret_data',
           'publicCell' => 'public_data',
       ];
   }
}