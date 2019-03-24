<?php

namespace Tienvx\Bundle\MbtBundle\Tests\Message;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Tienvx\Bundle\MbtBundle\Tests\TestCase;
use Tienvx\Messenger\MemoryTransport\Connection;

abstract class MessageTestCase extends TestCase
{
    /**
     * @var string
     */
    protected $logDir;

    /**
     * @var string
     */
    protected $screenshotsDir;

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();
        $this->runCommand('doctrine:database:drop --force');
        $this->runCommand('doctrine:database:create');
        $this->runCommand('doctrine:schema:create');

        /** @var ParameterBagInterface $params */
        $params = self::$container->get(ParameterBagInterface::class);
        $this->logDir = $params->get('kernel.logs_dir');
        $this->screenshotsDir = $params->get('mbt.screenshots_dir');
    }

    /**
     * @throws \Exception
     */
    protected function consumeMessages()
    {
        while (true) {
            $this->runCommand('messenger:consume-messages memory --limit=1');

            if (!$this->hasMessages()) {
                break;
            }
        }
    }

    protected function clearMessages()
    {
        /** @var Connection $connection */
        $connection = self::$container->get(Connection::class);
        $connection->clear();
    }

    protected function hasMessages()
    {
        /** @var Connection $connection */
        $connection = self::$container->get(Connection::class);
        return $connection->has();
    }

    protected function clearLog()
    {
        exec("rm -f {$this->logDir}/test.log");
    }

    protected function hasLog()
    {
        return file_exists("{$this->logDir}/test.log") && filesize("{$this->logDir}/test.log") !== 0;
    }

    protected function logHasScreenshot()
    {
        return strpos(file_get_contents("{$this->logDir}/test.log"), 'Screenshot') !== false;
    }

    protected function removeScreenshots()
    {
        exec("rm -rf {$this->screenshotsDir}/*");
    }

    protected function countScreenshots(int $bugId)
    {
        return count(glob($this->screenshotsDir . "/{$bugId}/*.*"));
    }
}
