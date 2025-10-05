<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

class TestObject
{
    /** This field is readonly in another version of the logger. */
    public string $publicReadonlySecretCell = 'public_readonly_secret_data';
    public string $publicSecretCell = 'public_secret_data';
    public string $publicCell = 'public_data';
    protected string $protectedCell = 'protected_data';

    public function __construct(
       string $publicReadonlySecretCell = 'public_readonly_secret_data',
       string $publicSecretCell = 'public_secret_data',
       string $publicCell = 'public_data',
       string $protectedCell = 'protected_data'
   )
   {
       $this->protectedCell = $protectedCell;
       $this->publicCell = $publicCell;
       $this->publicSecretCell = $publicSecretCell;
       $this->publicReadonlySecretCell = $publicReadonlySecretCell;
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