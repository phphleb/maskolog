# Maskolog

[![version](https://poser.pugx.org/phphleb/maskolog/v)](https://packagist.org/packages/phphleb/maskolog)
[![License: MIT](https://img.shields.io/badge/License-MIT%20(Free)-31C754.svg)](https://github.com/phphleb/maskolog/blob/master/LICENSE)
[![tests](https://github.com/phphleb/maskolog/actions/workflows/tests.yaml/badge.svg?event=push)](https://github.com/phphleb/maskolog/actions/workflows/tests.yaml)

**PSR-3 decorator for the Monolog logger with log-masking capabilities.**

<p align="left">
  <img src="https://github.com/phphleb/maskolog/blob/7d5c2c45556013d53b7a4cdd28213dbfcd8267ab/example.png" style="width: 600px" alt="maskolog" />
</p>

Supports PHP versions [v7.4 - 8.0](https://github.com/phphleb/maskolog/tree/1.x) and **v8.1+**

_From the author:_

When I joined a second relatively large company and faced the same requirement to mask logs on production servers, it became clear that this application security issue was broader and warranted a public library. This repository implements the concrete rules we needed to roll out across projects.

+ **Masking sensitive data in logs**. Masking is flexible and can be applied globally or selectively. You can also designate specific Monolog handlers on the same logger to bypass masking.
+ **Masking configuration data in exceptions**. Logged errors may include environment settings, tokens, and other secrets. This logger locates such values in the context and masks them by value.

This library is intended for corporate environments; you should be familiar enough to handle installation and usage details not covered in this brief guide.

## Installation
Installation is performed using Composer:

```bash
composer require phphleb/maskolog --no-dev
```

## Logger Assembly
The logger is built from two parts — a factory that creates Monolog instances and an initializer that uses that factory to construct the masking logger itself.

```php

$factory = new \Maskolog\ExampleManagedLoggerFactory();
$logger = new \Maskolog\Logger($factory);

```

Since this library only provides the abstract AbstractManagedLoggerFactory for creating and configuring a factory, the repository includes an ExampleManagedLoggerFactory demonstrating its use.
The repository also includes an ExampleLogger showing how to extend the logger with additional masking capabilities.

### Log Masking Methods

Whether an added item participates in the masking strategy depends on which logger method is used

+ `withMaskingProcessor` and `withMaskingProcessors` are used to add masking processors that implement MaskingProcessorInterface. For the factory, this corresponds to the global `pushMaskingProcessor` method in the context of the logger instance.

+ `withUnmaskingHandler` lets you register a separate Monolog handler that does not participate in masking, even when masking is active. For the factory, this corresponds to the global `pushUnmaskingHandler` method in the context of the logger instance.

Masking logger methods return a new instance of the logger with the added properties.

```php
$logger = $logger->withMaskingProcessors([PasswordMaskingProcessor::class => 'password'])
    ->info('Text using password: {password}', ['password' => 'secret_password']);
    
// 'Text using password: *REDACTED.PASSWORD*' if masking is enabled, otherwise 'Text using password: secret_password'.

$logger->withMaskingProcessors([
           StringMaskingProcessor::class => 'token',
           UrlMaskingProcessor::class => 'url',
     ])
    ->info('Text using sensitive data: {token}, {password}, {url}', ['password' => 'secret_password', 'token' => 'secret_token', 'url' => 'domain.ru?hash=secret_hash']);

// 'Text using sensitive data: sec*REDACTED*en, *REDACTED.PASSWORD*, domain.ru?hash=REDACTED' if masking is enabled.

```
You can find examples of these processors in the _/src/Processors/Masking/_ folder, and also create your own.
If such a record seems long to you and the names of the placeholder fields in the project will be standardized, create a shortened method in the class inherited from the logger and use it.
For example:

```php
public function withPasswordMasking(array $value = ['password', 'pass']): Logger
{
    return $this->withMaskingProcessors([PasswordMaskingProcessor::class => $value]);
}
```

Now you can use password masking like this:

```php
$logger->withPasswordMasking() 
       ->info('Text using password: {password}', ['password' => 'secret_password']);
```
This example is in the ExampleLogger class.

### Masking Data According to the Project Configuration

For masking according to the values from the transferred list, a separate masking processor has been created, which is best assigned globally in the factory:

```php
$configValues = array_values(['cdn_url' => 'cdn.site.domain', 'cdn_token' => 'secret_token']);

$this->pushMaskingProcessor(function($record) use ($configValues) {
    return (new ConfigMaskingProcessor($configValues))($record);
});
```
This processor is intended to prevent raw configuration data from appearing in error messages and contexts; therefore, by default it is limited to error levels only. Enabling informational levels in the processor constructor can cause performance issues when logging large volumes, because each message will require substring searches.

The project is expected to catch exceptions and log them via the masking logger.

### Specifying Exact Fields to Mask

In the examples above, masking processors were configured with field names that were searched throughout the entire context, regardless of nesting.
But what if the log context contains a nested array (for example, an API response) and you need to mask a specific field while leaving another field with the same name unmasked?
This is a rare case, but you can specify a field for masking more precisely:

```php
$logger->withMaskingProcessors(
    [StringMaskingProcessor::class => ['internal' => ['token']]]
    )->info('List of tokens', [
      'internal' => ['token' => 'secret_token'], // The token will be masked
      'external' => ['token' => 'public_token']  // The token will not be masked
    ]);
```
If you need to target a field at the top level, wrap it in array brackets — `['key']`.
To mask all nested values under a given key, provide an empty array for that key — `['list' => []]`.
You can also use numeric target keys (for example, `['list' => [0]]`), although this is not recommended.

### Masking for Objects in the Context

By default, the logger converts objects to arrays, so an object will be masked like a regular associative array of data.
However, this conversion changes the nesting structure, and you must take that into account when specifying masks directly for fields.
Global replacements at the logger level work the same as before (the field name becomes a key in the resulting array).

```php
class ExampleObject {
    public string $examplePassword = 'secret_password';
}
```
```php
  $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => 'examplePassword']
        )->info('Masked object', [new ExampleObject()]);
```
Direct indication of the target:
```php
  $logger->withMaskingProcessors(
            [PasswordMaskingProcessor::class => [
                [ExampleObject::class => ['examplePassword']],
            ]]
        )->info('Masked object', [new ExampleObject()]);
```

If automatic conversion is disabled, target objects will be passed to masking processors in their original form, so you will need to provide individual masks for each object.
As an example for repository usage, there are examples for converting a specific object type to the required array format - these are the `Psr7RequestProcessor` and `Psr7ResponseProcessor` classes.

### Masking Object Fields According to Attributes

To automatically mask an object's fields in log context, add the special `Mask` attribute.


```php
use Maskolog\Attributes\Mask;
class ExampleDto {      
    public function __construct(
         #[\SensitiveParameter]
         #[Mask]         
         readonly public string $secret,
         #[\SensitiveParameter]
         #[Mask(PasswordMaskingProcessor::class)]         
         readonly public string $password;
    ) {}
}
```

If a specific masking processor is not specified in the attribute, the default one will be used.

### Simplified Example of Integration in Symfony

```yaml
services:
   Maskolog\ExampleLogger:
     arguments:
       - 'debug' # Maximum logging level
       - 'php://stdout' # Main log handler
       - '%kernel.environment%'

   Psr\Log\LoggerInterface:
     alias: Maskolog\ExampleLogger

```
```php
public function index(ExampleLogger $exampleLogger, LoggerInterface $psrLogger): void
{
    $psrLogger->info('Global masks applied only');

    $exampleLogger->withPasswordMasking()
        ->info('Local password masking on top of global masks: {password}', ['password' => 'secret_password']);
}
```

### Demonstration of Symfony Service Initialization

```yaml
    services:
      Maskolog\ExampleLogger:
         arguments:
            - 'debug'

      Psr\Log\LoggerInterface:
          alias: Maskolog\ExampleLogger

```

### Initializing the Standard Monolog Logger

From this wrapper logger you can retrieve the currently initialized Monolog instance at any time, including all associated processors and handlers.
+ The `getMaskingLogger` method returns the logger with all resources, including masking processors, but excluding handlers that are not involved in masking.
+ The `getUnmaskingLogger` method returns the logger without masking processors, and includes only those handlers that are not involved in masking, if such handlers were assigned.

### Notes

+ If you use a Monolog logger instance obtained from the masking logger, be aware of limitations on how it can be used. First, you cannot add new masking processors to it. Second, context objects will be converted to arrays only after any processors already attached to the returned Monolog instance have run.
+ Examples show using the global processor `Monolog\Processor\PsrLogMessageProcessor` with the `removeUsedContextFields` option enabled to substitute context values into the log message.

### Conclusion

If you have suggestions to improve or extend the logger, contact the author or the Telegram [community](https://t.me/phphleb).

