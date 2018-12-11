<?php

namespace Tienvx\Bundle\MbtBundle\PathReducer;

use Doctrine\DBAL\LockMode;
use Exception;
use Throwable;
use Tienvx\Bundle\MbtBundle\Entity\Bug;
use Tienvx\Bundle\MbtBundle\Graph\Path;
use Tienvx\Bundle\MbtBundle\Helper\PathBuilder;
use Tienvx\Bundle\MbtBundle\Helper\Randomizer;
use Tienvx\Bundle\MbtBundle\Helper\PathRunner;
use Tienvx\Bundle\MbtBundle\Message\ReductionMessage;
use Tienvx\Bundle\MbtBundle\Subject\Subject;

class RandomPathReducer extends AbstractPathReducer
{
    /**
     * @param ReductionMessage $message
     * @throws Exception
     */
    public function handle(ReductionMessage $message)
    {
        $bug = $this->entityManager->find(Bug::class, $message->getBugId());

        if (!$bug || !$bug instanceof Bug) {
            return;
        }

        $model = $bug->getTask()->getModel();
        $subject = new class extends Subject {
        };
        $workflow = $this->workflowRegistry->get($subject, $model);

        $graph = $this->graphBuilder->build($workflow);
        $path = PathBuilder::build($bug->getPath());

        if ($bug->getLength() >= $message->getData()['length']) {
            // The reproduce path has not been reduced.
            list($i, $j) = $message->getData()['pair'];
            $newPath = PathBuilder::createWithShortestPath($graph, $path, $i, $j);
            // Make sure new path shorter than old path.
            if ($newPath->countPlaces() < $path->countPlaces()) {
                try {
                    $subject = $this->subjectManager->createSubject($model);
                    PathRunner::run($newPath, $workflow, $subject);
                } catch (Throwable $newThrowable) {
                    if ($newThrowable->getMessage() === $bug->getBugMessage()) {
                        $this->dispatch($bug->getId(), $newPath);
                    }
                }
            }
        }

        $this->postHandle($message);
    }

    /**
     * @param int $bugId
     * @param Path|null $newPath
     * @param ReductionMessage|null $message
     * @return int
     * @throws Exception
     */
    public function dispatch(int $bugId, Path $newPath = null, ReductionMessage $message = null): int
    {
        if (empty($newPath) && !empty($message)) {
            // Dispatch new messages only if found shorter replicated path.
            return 0;
        }

        $this->entityManager->beginTransaction();
        try {
            $bug = $this->entityManager->find(Bug::class, $bugId, LockMode::PESSIMISTIC_WRITE);

            if (!$bug || !$bug instanceof Bug) {
                return 0;
            }

            if ($newPath) {
                $bug->setPath(Path::serialize($newPath));
                $bug->setLength($newPath->countPlaces());
                $path = $newPath;
            } else {
                $path = Path::unserialize($bug->getPath());
            }

            if ($path->countPlaces() <= 2) {
                return 0;
            }

            $messagesCount = 0;
            $pairs = Randomizer::randomPairs($path->countPlaces(), $path->countPlaces());
            foreach ($pairs as $pair) {
                $message = new ReductionMessage($bug->getId(), static::getName(), [
                    'length' => $path->countPlaces(),
                    'pair' => $pair,
                ]);
                $this->messageBus->dispatch($message);
                $messagesCount++;
            }

            $bug->setMessagesCount($messagesCount);

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $messagesCount;
        } catch (Throwable $throwable) {
            // Something happen, ignoring.
            $this->entityManager->rollBack();
            return 0;
        }
    }

    public static function getName()
    {
        return 'random';
    }
}
