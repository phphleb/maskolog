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

    /**
     * @param AbstractManagedLoggerFactory|null $factory
     * @param string $maxLevel
     * @param string $stream
     * @param bool|string $enableMasking
     */
    public function __construct(
        ?AbstractManagedLoggerFactory $factory = null,
        string $maxLevel = LogLevel::ERROR,
        string $stream = 'php://stdout',
        $enableMasking = true
    )
    {
        // Passing an environment parameter to Symfony.
        if (is_string($enableMasking)) {
            $enableMasking = $enableMasking === 'prod';
        }

        if (!$factory) {
            $factory = new ExampleManagedLoggerFactory(
                $maxLevel,
                $stream,
                self::CHANNEL_NAME,
                self::CHANNEL_NAME,
                $enableMasking,
            );
        }

        parent::__construct($factory);
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