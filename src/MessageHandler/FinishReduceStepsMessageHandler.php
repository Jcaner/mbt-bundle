<?php

namespace Tienvx\Bundle\MbtBundle\MessageHandler;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Tienvx\Bundle\MbtBundle\Entity\Bug;
use Tienvx\Bundle\MbtBundle\Message\FinishReduceBugMessage;
use Tienvx\Bundle\MbtBundle\Message\FinishReduceStepsMessage;

class FinishReduceStepsMessageHandler implements MessageHandlerInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public function __construct(
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus
    ) {
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
    }

    public function __invoke(FinishReduceStepsMessage $message): void
    {
        $bugId = $message->getBugId();

        $bug = $this->updateMessagesCount($bugId);
        if ($bug instanceof Bug && 0 === $bug->getMessagesCount()) {
            $this->messageBus->dispatch(new FinishReduceBugMessage($bug->getId()));
        }
    }

    protected function updateMessagesCount(int $bugId): Bug
    {
        $callback = function () use ($bugId): Bug {
            $bug = $this->entityManager->find(Bug::class, $bugId, LockMode::PESSIMISTIC_WRITE);

            if (!$bug instanceof Bug) {
                throw new Exception(sprintf('No bug found for id %d', $bugId));
            }

            if ($bug->getMessagesCount() > 0) {
                $bug->setMessagesCount($bug->getMessagesCount() - 1);
            }

            return $bug;
        };

        return $this->entityManager->transactional($callback);
    }
}
