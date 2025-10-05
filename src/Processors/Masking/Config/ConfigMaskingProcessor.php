<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Processors\Masking\Config;

use Maskolog\Processors\AbstractContextMaskingProcessor;
use Maskolog\Processors\AbstractDataMaskingProcessor;
use Maskolog\Processors\Masking\Context\MaskingProcessor;
use Psr\Log\LogLevel;

/**
 * A processor with the ability to transfer a config
 * with sensitive settings and masking by config values.
 * Can be relevant for complex values, such as a hash.
 *
 * Searches and performs replacement both in the context
 * and in the text of messages.
 *
 * (!) For short values and whole words or parts of words,
 * it can replace settings with parts of arbitrary
 * message text and be deciphered through this.
 *
 * ```php
 * $configValues = array_values(['cdn_url' => 'cdn.site.domain', 'cdn_token' => 'm94a08da1f23a']);
 * $logger = $logger->withMaskingProcessor(function($record) use ($configValues) {
 *    return (new ConfigMaskingProcessor($configValues))($record);
 * });
 *
 * ```
 */
class ConfigMaskingProcessor extends AbstractDataMaskingProcessor
{
    /**
     * @var string[]
     */
    protected array $config;

    /**
     * @var array<int, string>
     */
    protected array $maskByLevels;
    protected AbstractContextMaskingProcessor $maskingProcessor;

    /**
     * Since searching in text is resource-intensive, it is recommended
     * to use it only in case of application errors.
     *
     * @param array<mixed> $config
     * @param array<int, string> $maskByLevels
     * @param AbstractContextMaskingProcessor|null $maskingProcessor
     */
    public function __construct(
        array   $config,
        array $maskByLevels = [
            LogLevel::WARNING,
            LogLevel::ERROR,
            LogLevel::CRITICAL,
            LogLevel::ALERT,
            LogLevel::EMERGENCY,
        ],
        ?AbstractContextMaskingProcessor $maskingProcessor = null
    )
    {
        $this->maskByLevels = $maskByLevels;
        $this->config = $this->flattenUniqueStrings($config);
        $this->maskingProcessor = $maskingProcessor ?? new MaskingProcessor();
    }

    /**
     * @param array<int|string, mixed> $context
     */
    protected function updateData(
        string                        $level,
        string &$message,
        array  &$context
    ): void
    {
        if (!in_array($level, $this->maskByLevels, true)) {
            return;
        }

        foreach ($this->config as $value) {
            if (mb_strpos($message, $value) === false) {
                continue;
            }
            /** @var string $maskedValue */
            $maskedValue = $this->maskingProcessor->addMask($value);
            $message = str_replace($value, $maskedValue, $message);
        }

        $context = $this->maskContextValues($context, $this->config);
    }

    /**
     * @param array<int|string, mixed> $context
     * @param array<int|string, string> $maskConfigValues
     * @return array<int|string, mixed>
     */
    private function maskContextValues(
        array $context,
        array $maskConfigValues
    ): array
    {
        foreach ($context as $k => $v) {
            if (is_array($v)) {
                $context[$k] = $this->maskContextValues($v, $maskConfigValues);
                continue;
            }
            if (is_string($v) && in_array($v, $maskConfigValues, true)) {
                $context[$k] = $this->maskingProcessor->addMask($v);
            }
        }
        return $context;
    }

    /**
     * @param array<mixed> $input
     * @return string[]
     */
    private function flattenUniqueStrings(array $input): array
    {
        $result = [];

        $walker = static function ($node) use (&$walker, &$result) {
            if (is_array($node)) {
                foreach ($node as $value) {
                    $walker($value);
                }
                return;
            }

            if (!is_string($node) || $node === '' || in_array($node, $result)) {
                return;
            }
            $result[] = $node;
        };

        $walker($input);

        return $result;
    }
}
