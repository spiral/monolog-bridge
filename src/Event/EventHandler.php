<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Monolog\Event;

use Monolog\Handler\AbstractHandler;
use Monolog\Logger;
use Spiral\Logger\Event\LogEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EventHandler extends AbstractHandler
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /**
     * @param int                      $level
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(int $level, EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        parent::__construct($level, true);
    }

    /**
     * @param array $record
     * @return bool|void
     */
    public function handle(array $record)
    {
        $this->dispatcher->dispatch(LogEvent::EVENT, new LogEvent(
            $record['datetime'],
            $record['channel'],
            strtolower(Logger::getLevelName($record['level'])),
            $record['message'],
            $record['context']
        ));
    }
}