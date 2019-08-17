<?php

namespace Tienvx\Bundle\MbtBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Registry;
use Tienvx\Bundle\MbtBundle\Entity\Bug;
use Tienvx\Bundle\MbtBundle\Entity\Task;
use Tienvx\Bundle\MbtBundle\Entity\Path;
use Tienvx\Bundle\MbtBundle\Helper\WorkflowHelper;

class CreateBugCommand extends AbstractCommand
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Registry
     */
    protected $workflowRegistry;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        Registry $workflowRegistry
    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->workflowRegistry = $workflowRegistry;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('mbt:bug:create')
            ->setDescription('Create a bug.')
            ->setHelp('Create a bug.')
            ->addArgument('title', InputArgument::REQUIRED, 'Bug title.')
            ->addArgument('path', InputArgument::REQUIRED, 'Bug path.')
            ->addArgument('message', InputArgument::REQUIRED, 'Bug message.')
            ->addArgument('task-id', InputArgument::REQUIRED, 'Task id.')
            ->addArgument('status', InputArgument::REQUIRED, 'Bug status.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $title = $input->getArgument('title');
        $path = $input->getArgument('path');
        $message = $input->getArgument('message');
        $taskId = $input->getArgument('task-id');
        $status = $input->getArgument('status');

        $task = $this->entityManager->getRepository(Task::class)->find($taskId);

        if (!$task || !$task instanceof Task) {
            $output->writeln(sprintf('No task found for id %d', $taskId));

            return;
        }

        $model = $task->getModel()->getName();
        $workflow = WorkflowHelper::get($this->workflowRegistry, $model);

        $bug = new Bug();
        $bug->setTitle($title);
        $bug->setPath(Path::deserialize($path));
        $bug->setModelHash(WorkflowHelper::checksum($workflow));
        $bug->setBugMessage($message);
        $bug->setTask($task);
        $bug->setStatus($status);

        $errors = $this->validator->validate($bug);

        if (count($errors) > 0) {
            $output->writeln((string) $errors);

            return;
        }

        $this->entityManager->persist($bug);
        $this->entityManager->flush();
    }
}
