<?php

namespace Tienvx\Bundle\MbtBundle\Steps\BuilderStrategy;

use Exception;
use Tienvx\Bundle\MbtBundle\Steps\Step;
use Tienvx\Bundle\MbtBundle\Steps\Steps;

class ReplaceStrategy implements StrategyInterface
{
    /**
     * @var array
     */
    private $middleSteps = [];

    public function __construct(array $middleSteps)
    {
        foreach ($this->middleSteps as $step) {
            if (!$step instanceof Step) {
                throw new Exception(sprintf('An object of type "%s" was expected, but got "%s".', Step::class, is_object($step) ? get_class($step) : gettype($step)));
            }
        }

        $this->middleSteps = $middleSteps;
    }

    public function create(Steps $original, int $from, int $to): Steps
    {
        if ($from >= $to) {
            throw new Exception('Can not replace steps');
        }

        $newSteps = new Steps();
        $this->addBeginSteps($original, $newSteps, $from);
        $this->addMiddleSteps($newSteps);
        $this->addEndSteps($original, $newSteps, $to);

        return $newSteps;
    }

    protected function addBeginSteps(Steps $original, Steps $newSteps, int $from): void
    {
        foreach ($original as $index => $step) {
            if ($index <= $from) {
                $newSteps->addStep($step);
            }
        }
    }

    protected function addMiddleSteps(Steps $newSteps): void
    {
        foreach ($this->middleSteps as $step) {
            $newSteps->addStep($step);
        }
    }

    protected function addEndSteps(Steps $original, Steps $newSteps, int $to): void
    {
        foreach ($original as $index => $step) {
            if ($index > $to) {
                $newSteps->addStep($step);
            }
        }
    }
}
