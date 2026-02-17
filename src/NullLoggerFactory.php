<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog;

use Monolog\Handler\NullHandler;
use Monolog\Logger;

class NullLoggerFactory extends AbstractManagedLoggerFactory
{
    protected function createMonologLogger(): Logger
    {
        $logger = new Logger('null');
        $logger->pushHandler(new NullHandler());

        return $logger;
    }
}