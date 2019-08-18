<?php

namespace Tienvx\Bundle\MbtBundle\Message;

class FinishReduceStepsMessage
{
    protected $bugId;

    public function __construct(int $bugId)
    {
        $this->bugId = $bugId;
    }

    public function getBugId(): int
    {
        return $this->bugId;
    }
}