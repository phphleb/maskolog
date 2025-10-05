<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog;

use Maskolog\Processors\Masking\Context\PasswordMaskingProcessor;
use Psr\Log\LogLevel;

/**
 * An example of full initialization of the logger
 * and extension by adding new methods.
 */
class ExampleLogger extends Logger
{
    protected const CHANNEL_NAME = 'example';

    public function __construct(
        string $maxLevel = LogLevel::ERROR,
        string $stream = 'php://stdout',
        bool   $enableMasking = true
    )
    {
        parent::__construct(new ExampleManagedLoggerFactory(
            $maxLevel,
            $stream,
            self::CHANNEL_NAME,
            self::CHANNEL_NAME,
            $enableMasking,
        ));
    }

    /**
     * An example of how you can extend the logger methods by adding your own.
     *
     * ```php
     *  $logger->withPasswordMasking()
     *     ->info('User password {password}', ['password' => 'secret']);
     * ```
     *
     * @param array<int|string, string|array<int|string, mixed>> $value
     */
    public function withPasswordMasking(array $value = ['password', 'pass']): Logger
    {
        return $this->withMaskingProcessors([PasswordMaskingProcessor::class => $value]);
    }
}