<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Processors\Masking\Context;

use Maskolog\Enums\UrlMaskingStatus;
use Maskolog\Processors\AbstractContextMaskingProcessor;
use Stringable;

/**
 * Masks GET parameters in specified URLs from the context,
 * preventing the creation of malicious links (XSS) in the logs themselves,
 * as well as hiding sensitive parameters.
 * If a processor for substituting context into a message is specified,
 * then it is masked in the message accordingly.
 */
class UrlMaskingProcessor extends AbstractContextMaskingProcessor
{
    /** @var array<int|string, mixed> */
    protected array $maskingRules = [];

    /**
     * You can specify specific URL GET parameter keys to mask only them.
     * If not specified, all parameters will be masked.
     *
     * For example:
     *
     * ['url' => ['password', 'token']] - the specified URL parameters will be masked according to the log context parameter 'url'.
     *
     * ```php
     *  $logger->withMaskingProcessor(function($record) {
     *       return (new UrlMaskingProcessor(['url']))
     *           ->useMaskedParams(['url' => ['password', 'token']])($record);
     *   })->info('Send to {url}', ['url' => 'https://example.domain/?token=abc&password=secret&method=add']);
     * ```
     *
     * @param array<int|string, mixed> $rules
     */
    public function useMaskedParams(array $rules): self
    {
        if ($rules) {
            $this->maskingRules = $rules;
            $this->cells = array_keys($rules);
        }

        return $this;
    }
    /**
     * {@inheritDoc}
     *
     * @param string|Stringable $value
     */
    public function addMask($value): string
    {
        $value = (string)$value;
        if (mb_strpos($value, '?') === false) {
            return $value;
        }
        $parts = explode('?', $value, 2);
        $host = '';
        $separator = '?';
        if (count($parts) == 1) {
            $queryStringWithTag = $parts[0];
        } else {
            $host = $parts[0];
            $queryStringWithTag = $parts[1];
        }
        $query = explode('#', $queryStringWithTag, 2);
        $tag = '';
        if (count($query) > 1) {
            $tag = '#' . $query[1];
        }
        $queryString = $query[0];

        return $host . $separator . $this->replace($queryString) . $tag;
    }

    protected function getReplacement(): string
    {
        return UrlMaskingStatus::REPLACEMENT;
    }

    private function replace(string $queryString): string
    {
        $queryParts = explode('&', $queryString);
        /** @var array<int|string, mixed> $keys */
        $keys = $this->maskingRules[$this->getCurrentCell()] ?? [];
        $keyNumber = 0;
        foreach ($queryParts as &$queryPart) {
            $items = explode('=', $queryPart, 2);
            if (count($items) > 1) {
                $key = $items[0];
                if (empty($keys) || in_array($key, $keys,true)) {
                    $queryPart = $key . '=' . $this->getReplacement();
                }
            } else {
                $key = $keyNumber;
                if (empty($keys) || in_array($key, $keys,true)) {
                    $queryPart = $this->getReplacement();
                }
            }
            $keyNumber++;
        }
        return implode('&', $queryParts);
    }
}
