<?php

namespace Tienvx\Bundle\MbtBundle\DependencyInjection\Compiler;

use Exception;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ReporterPass implements CompilerPassInterface
{
    use PluginTrait;

    private $reporterService;
    private $reporterTag;

    public function __construct(
        string $reporterService = 'mbt.reporter_manager',
        string $reporterTag = 'mbt.reporter'
    ) {
        $this->reporterService = $reporterService;
        $this->reporterTag = $reporterTag;
    }

    /**
     * @throws Exception
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->reporterService)) {
            return;
        }

        $reporters = $this->findPlugins($container, $this->reporterTag);

        $reporterDefinition = $container->getDefinition($this->reporterService);
        $reporterDefinition->replaceArgument(0, $reporters);
    }
}
