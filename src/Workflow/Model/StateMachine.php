<?php

namespace Tienvx\Bundle\MbtBundle\Workflow\Model;

use Exception;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\StateMachine as BaseStateMachine;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tienvx\Bundle\MbtBundle\Helper\ModelHelper;
use Tienvx\Bundle\MbtBundle\Subject\SubjectInterface;

class StateMachine extends BaseStateMachine
{
    /**
     * @var ModelHelper
     */
    protected $modelHelper;

    public function __construct(Definition $definition, MarkingStoreInterface $markingStore = null, EventDispatcherInterface $dispatcher = null, string $name = 'unnamed', ModelHelper $modelHelper)
    {
        parent::__construct($definition, $markingStore, $dispatcher, $name);
        $this->modelHelper = $modelHelper;
    }

    public function apply(object $subject, string $transitionName, array $context = [])
    {
        if (!$subject instanceof SubjectInterface) {
            return parent::apply($subject, $transitionName, $context);
        }

        if (!$this->can($subject, $transitionName)) {
            throw new Exception(sprintf('Can not apply transition %s', $transitionName));
        }

        return $this->modelHelper->apply($subject, $transitionName, $this->getDefinition(), $this->getMarkingStore(), $this->getMarking($subject), $context);
    }
}