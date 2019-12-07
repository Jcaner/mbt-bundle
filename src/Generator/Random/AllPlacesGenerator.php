<?php

namespace Tienvx\Bundle\MbtBundle\Generator\Random;

use Symfony\Component\Workflow\Workflow;
use Tienvx\Bundle\MbtBundle\Entity\GeneratorOptions;
use Tienvx\Bundle\MbtBundle\Subject\SubjectInterface;

class AllPlacesGenerator extends RandomGeneratorTemplate
{
    /**
     * @var int
     */
    protected $maxSteps = 300;

    public function setMaxSteps(int $maxSteps): void
    {
        $this->maxSteps = $maxSteps;
    }

    public static function getName(): string
    {
        return 'all-places';
    }

    public function getLabel(): string
    {
        return 'All Places';
    }

    public static function support(): bool
    {
        return true;
    }

    protected function initState(Workflow $workflow, GeneratorOptions $generatorOptions): array
    {
        return [
            'stepsCount' => 1,
            'maxSteps' => $generatorOptions->getMaxSteps() ?? $this->maxSteps,
            'visitedPlaces' => $workflow->getDefinition()->getInitialPlaces(),
            'totalPlaces' => count($workflow->getDefinition()->getPlaces()),
        ];
    }

    protected function updateState(Workflow $workflow, SubjectInterface $subject, string $transitionName, array &$state): void
    {
        ++$state['stepsCount'];

        foreach ($workflow->getMarking($subject)->getPlaces() as $place => $status) {
            if ($status && !in_array($place, $state['visitedPlaces'])) {
                $state['visitedPlaces'][] = $place;
            }
        }
    }

    protected function canStop(array $state): bool
    {
        return count($state['visitedPlaces']) === $state['totalPlaces'] || $state['stepsCount'] >= $state['maxSteps'];
    }

    protected function getTransition(Workflow $workflow, SubjectInterface $subject, array $state): ?string
    {
        $transitions = $this->getAvailableTransitions($workflow, $subject, $state);
        $transitionName = $this->randomTransition($workflow, $subject, $transitions);
        if (!is_null($transitionName)) {
            return $transitionName;
        }

        $remainTransitions = $this->getAvailableTransitions($workflow, $subject, $state, true);

        return $this->randomTransition($workflow, $subject, $remainTransitions);
    }

    protected function getAvailableTransitions(Workflow $workflow, SubjectInterface $subject, array $state, bool $visited = false): array
    {
        $transitions = [];
        $marking = $workflow->getMarking($subject);
        foreach ($workflow->getDefinition()->getTransitions() as $transition) {
            foreach ($transition->getFroms() as $place) {
                if (!$marking->has($place)) {
                    break;
                }
            }
            if (array_diff($transition->getTos(), $state['visitedPlaces']) && !$visited) {
                $transitions[$transition->getName()] = 1;
            } elseif (!array_diff($transition->getTos(), $state['visitedPlaces']) && $visited) {
                $transitions[$transition->getName()] = 1;
            }
        }

        return $transitions;
    }
}
