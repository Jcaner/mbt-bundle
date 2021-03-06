<?php

namespace Tienvx\Bundle\MbtBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Generator extends Constraint
{
    /**
     * @var string
     */
    protected $message = '"{{ string }}" is not a valid or supported generator.';

    public function getMessage(): string
    {
        return $this->message;
    }
}
