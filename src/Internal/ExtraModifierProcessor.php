<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Internal;

use Monolog\Processor\ProcessorInterface;

/**
 * @internal
 */
class ExtraModifierProcessor implements ProcessorInterface
{
    /**
     * @var array<int|string, mixed>
     */
    private $extra;

    /**
     * @param array<int|string, mixed> $extra
     */
    public function __construct(array $extra)
    {
        $this->extra = $extra;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(array $record): array
    {
        $record['extra'] = array_merge($record['extra'], $this->extra);

        return $record;
    }
}