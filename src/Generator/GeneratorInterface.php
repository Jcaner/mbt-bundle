<?php

namespace Tienvx\Bundle\MbtBundle\Generator;

use Symfony\Component\Workflow\Workflow;
use Tienvx\Bundle\MbtBundle\Entity\GeneratorOptions;
use Tienvx\Bundle\MbtBundle\Model\SubjectInterface;
use Tienvx\Bundle\MbtBundle\Plugin\PluginInterface;

interface GeneratorInterface extends PluginInterface
{
    public function generate(Workflow $workflow, SubjectInterface $subject, GeneratorOptions $generatorOptions): iterable;
}
