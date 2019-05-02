<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Monolog;

use Monolog\Handler\AbstractHandler;
use Monolog\Logger;
use Spiral\Logger\Event\LogEvent;

final class EventHandler extends AbstractHandler
{
    /** @var callable[] */
    private $listeners = [];

    /**
     * @param callable $listener
     */
    public function addListener(callable $listener)
    {
        if (!array_search($listener, $this->listeners, true)) {
            $this->listeners[] = $listener;
        }
    }

    /**
     * @param callable $listener
     */
    public function removeListener(callable $listener)
    {
        $key = array_search($listener, $this->listeners, true);
        if ($key !== null) {
            unset($this->listeners[$key]);
        }
    }

    /**
     * @param array $record
     * @return bool|void
     */
    public function handle(array $record)
    {
        $e = new LogEvent(
            $record['datetime'],
            $record['channel'],
            strtolower(Logger::getLevelName($record['level'])),
            $record['message'],
            $record['context']
        );

        foreach ($this->listeners as $listener) {
            call_user_func($listener, $e);
        }
    }
}