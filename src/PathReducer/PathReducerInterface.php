<?php

namespace Tienvx\Bundle\MbtBundle\PathReducer;

use Symfony\Component\Workflow\Workflow;
use Tienvx\Bundle\MbtBundle\DependencyInjection\Compiler\PluginInterface;
use Tienvx\Bundle\MbtBundle\Entity\Bug;

interface PathReducerInterface extends PluginInterface
{
    public function handle(Bug $bug, Workflow $workflow, int $length, int $from, int $to);

    public function dispatch(Bug $bug): int;

    public function getLabel(): string;
}
