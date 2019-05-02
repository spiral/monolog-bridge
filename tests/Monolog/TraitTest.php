<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Logger\Tests;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Spiral\Boot\BootloadManager;
use Spiral\Config\ConfigManager;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Config\LoaderInterface;
use Spiral\Core\Container;
use Spiral\Core\ContainerScope;
use Spiral\Logger\Traits\LoggerTrait;
use Spiral\Monolog\Bootloader\MonologBootloader;
use Spiral\Monolog\Config\MonologConfig;

class TraitTest extends TestCase
{
    use LoggerTrait;

    public function setUp()
    {
        $this->logger = null;
    }

    public function testNoScope()
    {
        $logger = $this->getLogger();
        $this->assertInstanceOf(NullLogger::class, $this->getLogger());
        $this->assertSame($logger, $this->getLogger());
    }

    public function testSetLogger()
    {
        $logger = new NullLogger();
        $this->setLogger($logger);
        $this->assertSame($logger, $this->getLogger());
    }

    public function testScope()
    {
        $container = new Container();
        $container->bind(ConfiguratorInterface::class, new ConfigManager(
            new class implements LoaderInterface
            {
                public function has(string $section): bool
                {
                    return false;
                }

                public function load(string $section): array
                {
                    return [];
                }
            }
        ));
        $container->get(BootloadManager::class)->bootload([MonologBootloader::class]);
        $container->bind(MonologConfig::class, new MonologConfig());

        ContainerScope::runScope($container, function () {
            $this->assertInstanceOf(Logger::class, $this->getLogger());
            $this->assertSame(self::class, $this->getLogger()->getName());
        });
    }
}