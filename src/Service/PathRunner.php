<?php

namespace Tienvx\Bundle\MbtBundle\Service;

use Tienvx\Bundle\MbtBundle\Graph\Path;
use Tienvx\Bundle\MbtBundle\Model\Model;

class PathRunner
{
    /**
     * @param Path $path
     * @param Model $model
     * @throws \Exception
     */
    public function run(Path $path, Model $model)
    {
        $subject = $model->createSubject();

        foreach ($path->getEdges() as $index => $edge) {
            $canApply = $model->applyModel($subject, $edge, $path, $index);
            if (!$canApply) {
                break;
            }
        }
    }
}
