<?php

namespace Tienvx\Bundle\MbtBundle\MessageHandler;

use Exception;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Tienvx\Bundle\MbtBundle\Command\CommandRunner;
use Tienvx\Bundle\MbtBundle\Message\CreateBugMessage;

class CreateBugMessageHandler implements MessageHandlerInterface
{
    /**
     * @var CommandRunner
     */
    private $commandRunner;

    public function __construct(CommandRunner $commandRunner)
    {
        $this->commandRunner = $commandRunner;
    }

    /**
     * @param CreateBugMessage $message
     *
     * @throws Exception
     */
    public function __invoke(CreateBugMessage $message)
    {
        $title = $message->getTitle();
        $steps = $message->getSteps();
        $bugMessage = $message->getMessage();
        $taskId = $message->getTaskId();
        $status = $message->getStatus();
        $model = $message->getModel();
        $this->commandRunner->run(['mbt:bug:create', $title, $steps, $bugMessage, $taskId, $status, $model]);
    }
}
