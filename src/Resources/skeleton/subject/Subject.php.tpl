<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Tienvx\Bundle\MbtBundle\Annotation\Subject;
use Tienvx\Bundle\MbtBundle\Annotation\Transition;
use Tienvx\Bundle\MbtBundle\Annotation\Place;
use Tienvx\Bundle\MbtBundle\Steps\Data;
use Tienvx\Bundle\MbtBundle\Subject\AbstractSubject;

/**
* @Subject("<?= $workflow; ?>")
*/
class <?= $class_name; ?> extends AbstractSubject
{
    public function aGuard(): bool
    {
        return true;
    }
<?php foreach ($places as $place => $method): ?>

    /**
     * @Place("<?= $place; ?>")
     */
    public function <?= $method; ?>()
    {
    }
<?php endforeach; ?>
<?php foreach ($transitions as $transition => $method): ?>

    /**
     * @Transition("<?= $transition; ?>")
     */
    public function <?= $method; ?>(Data $data)
    {
        $value = $data->getSet('key', $missCallback, $validateCallback);
    }
<?php endforeach; ?>
}
