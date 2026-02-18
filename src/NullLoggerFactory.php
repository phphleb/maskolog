<?php
/**
 * @author Foma Tuturov <fomiash@yandex.ru>
 */

declare(strict_types=1);

namespace Maskolog;

use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

class NullLoggerFactory extends AbstractManagedLoggerFactory
{
    public function __construct()
    {
        parent::__construct(LogLevel::INFO, true);
    }

    protected function createMonologLogger(): Logger
    {
        $logger = new Logger('null');
        $logger->pushHandler(new NullHandler());

        return $logger;
    }
}