<?php

namespace App\Subject;

use Exception;
use Tienvx\Bundle\MbtBundle\Annotation\Subject;
use Tienvx\Bundle\MbtBundle\Annotation\Transition;
use Tienvx\Bundle\MbtBundle\Subject\AbstractSubject;

/**
 * @Subject("product")
 */
class Product extends AbstractSubject
{
    /**
     * @Transition("selectFile")
     *
     * @throws Exception
     */
    public function selectFile()
    {
        throw new Exception('Can not upload file!');
    }

    public function getScreenshotUrl($bugId, $index)
    {
        return sprintf('http://localhost/mbt-api/bug-screenshot/%d/%d', $bugId, $index);
    }
}
