<?php

namespace Tienvx\Bundle\MbtBundle\DependencyInjection\Compiler;

use Exception;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Tienvx\Bundle\MbtBundle\Command\ExecuteTaskCommand;
use Tienvx\Bundle\MbtBundle\Command\GeneratePathCommand;
use Tienvx\Bundle\MbtBundle\Validator\Constraints\ModelValidator;

class WorkflowRegistryPass implements CompilerPassInterface
{
    private $pathReducerTag;
    private $workflowRegistry;

    public function __construct(
        string $pathReducerTag = 'mbt.path_reducer',
        string $workflowRegistry = 'workflow.registry'
    ) {
        $this->pathReducerTag   = $pathReducerTag;
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * @param ContainerBuilder $container
     * @throws Exception
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition($this->workflowRegistry)) {
            $workflowRegistry = new Reference($this->workflowRegistry);

            foreach ($container->findTaggedServiceIds($this->pathReducerTag, true) as $serviceId => $attributes) {
                $pathReducer = $container->getDefinition($serviceId);
                $pathReducer->addMethodCall('setWorkflowRegistry', [$workflowRegistry]);
            }

            $executeTaskCommandDefinition = $container->getDefinition(ExecuteTaskCommand::class);
            $executeTaskCommandDefinition->addMethodCall('setWorkflowRegistry', [$workflowRegistry]);

            $generatePathCommandDefinition = $container->getDefinition(GeneratePathCommand::class);
            $generatePathCommandDefinition->addMethodCall('setWorkflowRegistry', [$workflowRegistry]);

            $modelValidatorDefinition = $container->getDefinition(ModelValidator::class);
            $modelValidatorDefinition->addMethodCall('setWorkflowRegistry', [$workflowRegistry]);
        }
    }
}