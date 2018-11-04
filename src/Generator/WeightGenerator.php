<?php

namespace Tienvx\Bundle\MbtBundle\Generator;

use Generator;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Tienvx\Bundle\MbtBundle\Helper\Randomizer;
use Tienvx\Bundle\MbtBundle\Subject\Subject;

class WeightGenerator extends AbstractGenerator
{
    /**
     * @var int
     */
    protected $maxPathLength = 300;

    public function setMaxPathLength(int $maxPathLength)
    {
        $this->maxPathLength = $maxPathLength;
    }

    public function getAvailableTransitions(Workflow $workflow, Subject $subject): Generator
    {
        $pathLength    = 0;
        $maxPathLength = $this->maxPathLength;

        while (true) {
            /** @var Transition[] $transitions */
            $transitions = $workflow->getEnabledTransitions($subject);
            if (!empty($transitions)) {
                $transitionsByWeight = [];
                foreach ($transitions as $index => $transition) {
                    $transitionMetadata = $workflow->getDefinition()->getMetadataStore()->getTransitionMetadata($transition);
                    $transitionsByWeight[$transition->getName()] = $transitionMetadata['weight'] ?? 1;
                }
                $transitionName = Randomizer::randomByWeight($transitionsByWeight);

                yield $transitionName;

                // Update current state.
                $pathLength++;

                if ($pathLength >= $maxPathLength) {
                    break;
                }
            } else {
                break;
            }
        }
    }

    public static function getName()
    {
        return 'weight';
    }
}
