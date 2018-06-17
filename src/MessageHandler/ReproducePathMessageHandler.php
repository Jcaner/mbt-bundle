<?php

namespace Tienvx\Bundle\MbtBundle\MessageHandler;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Process\Process;
use Tienvx\Bundle\MbtBundle\Message\ReproducePathMessage;

class ReproducePathMessageHandler implements MessageHandlerInterface
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * @param ReproducePathMessage $reproducePathMessage
     * @throws \Exception
     */
    public function __invoke(ReproducePathMessage $reproducePathMessage)
    {
        $process = new Process('bin/console mbt:reduce-reproduce-path ' . $reproducePathMessage->getId());
        $process->setWorkingDirectory($this->params->get('kernel.project_dir'));

        $process->run();
    }
}
