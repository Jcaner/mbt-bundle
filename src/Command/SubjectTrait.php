<?php

namespace Tienvx\Bundle\MbtBundle\Command;

use Exception;
use Tienvx\Bundle\MbtBundle\Subject\SubjectInterface;
use Tienvx\Bundle\MbtBundle\Subject\SubjectManager;

trait SubjectTrait
{
    /**
     * @var SubjectManager
     */
    private $subjectManager;

    /**
     * @param string $model
     *
     * @return SubjectInterface
     *
     * @throws Exception
     */
    protected function getSubject(string $model): SubjectInterface
    {
        $subject = $this->subjectManager->createSubject($model);
        $subject->setUp();

        return $subject;
    }
}
