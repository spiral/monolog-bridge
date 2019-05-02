<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Monolog;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;
use Spiral\Core\Container\InjectorInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Core\FactoryInterface;
use Spiral\Logger\LogsInterface;
use Spiral\Monolog\Config\MonologConfig;
use Spiral\Monolog\Exception\ConfigException;

final class LogFactory implements LogsInterface, InjectorInterface, SingletonInterface
{
    // Default logger channel (supplied via injection)
    public const DEFAULT = 'default';

    // Name of log event fired by global log handler
    public const LOG_EVENT = 'log';

    /** @var MonologConfig */
    private $config;

    /** @var LoggerInterface */
    private $default;

    /** @var FactoryInterface */
    private $factory;

    /** @var HandlerInterface|null */
    private $eventHandler;

    /**
     * @param MonologConfig    $config
     * @param FactoryInterface $factory
     */
    public function __construct(MonologConfig $config, FactoryInterface $factory)
    {
        $this->config = $config;
        $this->factory = $factory;
        $this->eventHandler = new EventHandler($config->getEventLevel());
    }

    /**
     * @inheritdoc
     */
    public function getLogger(string $channel = null): LoggerInterface
    {
        if ($channel === null || $channel == self::DEFAULT) {
            if (!empty($this->default)) {
                // we should use only one default logger per system
                return $this->default;
            }

            return $this->default = new Logger(
                self::DEFAULT,
                $this->getHandlers(self::DEFAULT),
                $this->getProcessors(self::DEFAULT)
            );
        }

        return new Logger(
            $channel,
            $this->getHandlers($channel),
            $this->getProcessors($channel)
        );
    }

    /**
     * @inheritdoc
     */
    public function createInjection(\ReflectionClass $class, string $context = null)
    {
        // always return default logger as injection
        return $this->getLogger();
    }

    /**
     * @param callable $listener
     */
    public function addListener(callable $listener)
    {
        $this->eventHandler->addListener($listener);
    }

    /**
     * @param callable $listener
     */
    public function removeListener(callable $listener)
    {
        $this->eventHandler->removeListener($listener);
    }

    /**
     * Get list of channel specific handlers.
     *
     * @param string $channel
     * @return array
     *
     * @throws ConfigException
     */
    protected function getHandlers(string $channel): array
    {
        // always include default handler
        $handlers = [];

        foreach ($this->config->getHandlers($channel) as $handler) {
            try {
                $handlers[] = $handler->resolve($this->factory);
            } catch (ContainerExceptionInterface $e) {
                throw new ConfigException($e->getMessage(), $e->getCode(), $e);
            }
        }

        $handlers[] = $this->eventHandler;

        return $handlers;
    }

    /**
     * Get list of channel specific log processors. Falls back to PsrLogMessageProcessor for now.
     *
     * @param string $channel
     * @return callable[]
     */
    protected function getProcessors(string $channel): array
    {
        return [new PsrLogMessageProcessor()];
    }
}