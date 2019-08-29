<?php

namespace Tienvx\Bundle\MbtBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Tienvx\Bundle\MbtBundle\Command\ExecuteTaskCommand;
use Tienvx\Bundle\MbtBundle\Command\TestBugCommand;
use Tienvx\Bundle\MbtBundle\Command\TestPredefinedCaseCommand;
use Tienvx\Bundle\MbtBundle\Entity\PredefinedCase;
use Tienvx\Bundle\MbtBundle\Entity\Steps;
use Tienvx\Bundle\MbtBundle\Generator\GeneratorInterface;
use Tienvx\Bundle\MbtBundle\Generator\ProbabilityGenerator;
use Tienvx\Bundle\MbtBundle\Generator\RandomGenerator;
use Tienvx\Bundle\MbtBundle\PredefinedCase\PredefinedCaseManager;
use Tienvx\Bundle\MbtBundle\Reducer\ReducerInterface;
use Tienvx\Bundle\MbtBundle\Reporter\EmailReporter;
use Tienvx\Bundle\MbtBundle\Reporter\ReporterInterface;
use Tienvx\Bundle\MbtBundle\Reporter\SlackReporter;
use Tienvx\Bundle\MbtBundle\Subject\SubjectInterface;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class TienvxMbtExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerCommandConfiguration($config, $container);
        $this->registerGeneratorConfiguration($config, $container);
        $this->registerPredefinedCasesConfiguration($config, $container, $loader);

        $container->registerForAutoconfiguration(GeneratorInterface::class)
            ->setLazy(true)
            ->addTag('mbt.generator');
        $container->registerForAutoconfiguration(ReducerInterface::class)
            ->setLazy(true)
            ->addTag('mbt.reducer');
        $container->registerForAutoconfiguration(SubjectInterface::class)
            ->setLazy(true)
            ->addTag('mbt.subject');
        $container->registerForAutoconfiguration(ReporterInterface::class)
            ->setLazy(true)
            ->addTag('mbt.reporter');
    }

    private function registerCommandConfiguration(array $config, ContainerBuilder $container)
    {
        $commands = [
            ExecuteTaskCommand::class,
            TestBugCommand::class,
            TestPredefinedCaseCommand::class,
        ];
        foreach ($commands as $command) {
            $commandDefinition = $container->getDefinition($command);
            $commandDefinition->addMethodCall('setDefaultBugTitle', [$config['default_bug_title']]);
        }
    }

    private function registerGeneratorConfiguration(array $config, ContainerBuilder $container)
    {
        $randomGeneratorDefinition = $container->getDefinition(RandomGenerator::class);
        $randomGeneratorDefinition->addMethodCall('setMaxSteps', [$config['max_steps']]);
        $randomGeneratorDefinition->addMethodCall('setTransitionCoverage', [$config['transition_coverage']]);
        $randomGeneratorDefinition->addMethodCall('setPlaceCoverage', [$config['place_coverage']]);

        $probabilityGeneratorDefinition = $container->getDefinition(ProbabilityGenerator::class);
        $probabilityGeneratorDefinition->addMethodCall('setMaxSteps', [$config['max_steps']]);

        $slackReporterDefinition = $container->getDefinition(SlackReporter::class);
        $slackReporterDefinition->addMethodCall('setSlackHookUrl', [$config['slack_hook_url']]);
        $slackReporterDefinition->addMethodCall('setSlackFrom', [$config['slack_from']]);
        $slackReporterDefinition->addMethodCall('setSlackTo', [$config['slack_to']]);
        $slackReporterDefinition->addMethodCall('setSlackMessage', [$config['slack_message']]);

        $emailReporterDefinition = $container->getDefinition(EmailReporter::class);
        $emailReporterDefinition->addMethodCall('setEmailFrom', [$config['email_from']]);
        $emailReporterDefinition->addMethodCall('setEmailTo', [$config['email_to']]);
        $emailReporterDefinition->addMethodCall('setEmailSubject', [$config['email_subject']]);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     * @param XmlFileLoader    $loader
     *
     * @throws Exception
     */
    private function registerPredefinedCasesConfiguration(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $managerDefinition = $container->getDefinition(PredefinedCaseManager::class);

        foreach ($config['predefined_cases'] as $name => $case) {
            $caseDefinition = new Definition(PredefinedCase::class);
            $caseDefinition->setPublic(false);
            $caseDefinition->addMethodCall('init', [$name, $case['title'], $case['model'], Steps::denormalize($case['steps'])->serialize()]);
            $id = sprintf('predefined_case.%s', $name);
            $container->setDefinition($id, $caseDefinition);

            $managerDefinition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
