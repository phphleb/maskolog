<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Processors;

use Maskolog\Extensions\ArrayMatchesObjectDefinitionTrait;
use Monolog\LogRecord;

/**
 * The basis for a context masking processor.
 * If a processor for substituting context into a message is specified,
 * then it is masked in the message accordingly.
 */
abstract class AbstractContextMaskingProcessor implements MaskingProcessorInterface
{
    use ArrayMatchesObjectDefinitionTrait;

    private null|int|string $currentCell = null;

    /**
     * @param array<int|string, int|string|array<int|string, mixed>> $cells
     */
    public function __construct(protected array $cells = [])
    {
    }

    /**
     * @param LogRecord $record
     * @return LogRecord
     */
    final public function __invoke(#[\SensitiveParameter] LogRecord $record): LogRecord
    {
        return $record->with(context: $this->updateContext($record->context));
    }

    /**
     * @param array<int|string, mixed> $context
     * @return array<int|string, mixed>
     */
    private function updateContext(#[\SensitiveParameter] array $context): array
    {
        if ($this->cells) {
            foreach ($this->cells as $key => $cell) {
                if (is_int($key) && (is_string($cell) || is_int($cell))) {
                    /**
                     * @var array<int|string, mixed> $context
                     */
                    $context = $this->maskValueByKey($cell, $context);
                } else {
                    /**
                     * @var string|int|array<int|string, array<int|string, mixed>> $cell
                     * @var array<int|string, mixed> $context
                     */
                    $context = $this->maskValueByPersonalKey($key, $cell, $context);
                }
            }
        } else {
            /**
             * @var array<int|string, mixed> $context
             */
            $context = $this->maskAllValues($context);
        }

        return $context;
    }

    /**
     * @param array<int|string, mixed> $array
     * @return array<int|string, mixed>
     */
    private function maskValueByKey(int|string $cell, #[\SensitiveParameter] array $array): array
    {
        foreach ($array as $k => $v) {
            if ($this->compareWithCriteria($k, $cell)) {
                $array[$k] = $this->prepareMask($cell, $v);
                continue;
            }
            if (is_array($v)) {
                $array[$k] = $this->maskValueByKey($cell, $v);
            }
        }

        return $array;
    }

    /**
     * @param string|int|array<int|string, array<int|string, mixed>> $cell
     * @param array<int|string, mixed> $array
     * @return array<int|string, mixed>
     */
    private function maskValueByPersonalKey(
        string|int $key,
        string|int|array $cell,
        #[\SensitiveParameter] array $array,
    ): array
    {
        if (!is_array($cell) && array_key_exists($cell, $array) && is_object($array[$cell] ?? null)) {
            $array[$cell] = $this->prepareMask($cell, $array[$cell]);
            return $array;
        }
        // If it is a direct match.
        if (!is_array($cell) && array_key_exists($cell, $array)) {
            $array[$cell] = $this->prepareMask($cell, $array[$cell]);
            return $array;
        }
        // If an empty array is specified, everything is masked.
        if (is_array($cell) && count($cell) === 0 && is_array($array[$key] ?? null)) {
            $array[$key] = $this->maskAllValues($array[$key]);
            return $array;
        }
        // If this is a numeric key pointing to an array.
        if (is_int($key) && is_array($cell)) {
            // Attempting to define and process a converted object into an array.
            if ($this->matchesObjectShape($cell) && !is_object($array[$key] ?? null)) {
                $class = key($cell);
                $cell = current($cell);
                /**
                 * @var int $key
                 * @var string $class
                 * @var array<int|string, mixed> $data
                 * @var string|int|array<int|string, array<int|string, mixed>> $cell
                 * @var array<int, array<int|string, mixed>> $array
                 */
                $data = $array[$key][$class] ?? null;
                if ($data) {
                    $array[$key][$class] = $this->maskValueByPersonalKey($key, $cell, $data);
                }
                return $array;
            }
                /**
                 * @var array<int|string, int|string|array<int|string, mixed>|object> $cell
                 */
                foreach($cell as $k => $c) {
                    if (is_array($c) || is_object($c)) {
                         continue;
                    }
                    if (array_key_exists($c, $array)) {
                        $array[$c] = is_array($array[$c])
                            ? $this->maskValueByPersonalKey($k, $c, $array[$c])
                            : $this->prepareMask($c, $array[$c]);
                    }
                }
            return $array;
        }
        // Nested search.
        if (is_array($cell) && array_key_exists($key, $array) && $array[$key] && !is_object($array[$key])) {
            foreach($cell as $k => $c) {
                /**
                 * @var string|int|array<int|string, array<int|string, mixed>> $c
                 * @var array<int|string, array<int|string, mixed>> $array
                 */
                 $array[$key] = $this->maskValueByPersonalKey($k, $c, $array[$key]);
            }
        }
        return $array;
    }

    /**
     * @param array<int|string, mixed> $array
     * @return array<int|string, mixed>
     */
    private function maskAllValues(#[\SensitiveParameter] array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->maskAllValues($value);
            } else {
                $array[$key] = $this->prepareMask($key, $value);
            }
        }
        return $array;
    }

    /**
     * Process or replace a value.
     * The return result may vary depending on the masking conditions.
     * Can be used externally as an ability of this class.
     */
    abstract public function addMask(mixed $value): mixed;

    /**
     * Returns the current key by which the value is masked.
     */
    protected function getCurrentCell(): null|int|string
    {
        return $this->currentCell;
    }

    protected function compareWithCriteria(mixed $a, mixed $b): bool
    {
        if (is_string($a) && is_string($b)) {
           return mb_strtolower($a) === mb_strtolower($b);
        }
        return $a === $b;
    }

    private function prepareMask(int|string $cell, #[\SensitiveParameter] mixed $value): mixed
    {
        $this->currentCell = $cell;

        return $this->addMask($value);
    }
}
