<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog\Internal;

use Monolog\Logger;
use WeakMap;

/**
 * @internal
 *
 * Allows you to serialize a logger with a WeakMap.
 */
class SerializableLoggerMap
{
    /** @var WeakMap<Logger, bool> */
   private WeakMap $map;

   public function __construct()
   {
       $this->map = new WeakMap();
   }

    /** @return WeakMap<Logger, bool> */
    public function all(): WeakMap
    {
        return $this->map;
    }

    public function add(Logger $resource): void
    {
        $this->map[$resource] = true;
    }

    /** @return array<string, mixed> */
    public function __serialize(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->map = new WeakMap();
    }
}