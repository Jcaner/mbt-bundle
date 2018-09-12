<?php

namespace Tienvx\Bundle\MbtBundle\Tests\Message;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Tienvx\Bundle\MbtBundle\Entity\Bug;
use Tienvx\Bundle\MbtBundle\Entity\Task;
use Tienvx\Bundle\MbtBundle\Graph\Path;

class BugMessageTest extends MessageTestCase
{
    /**
     * @param array $pathArgs
     * @param string $reducer
     * @param string $reporter
     * @param array $expectedPathArgs
     * @dataProvider consumeMessageData
     */
    public function testExecute(array $pathArgs, string $reducer, string $reporter, array $expectedPathArgs)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $path = new Path(...$pathArgs);
        $expectedPath = new Path(...$expectedPathArgs);

        $task = new Task();
        $task->setTitle('Test task title');
        $task->setModel('shopping_cart');
        $task->setGenerator('random');
        $task->setReducer($reducer);
        $task->setReporter($reporter);
        $task->setProgress(0);
        $task->setStatus('not-started');
        $entityManager->persist($task);

        $entityManager->flush();

        $this->clearMessages();
        $this->clearHipchatMessages();

        $bug = new Bug();
        $bug->setTitle('Test bug title');
        $bug->setStatus('unverified');
        $bug->setPath(serialize($path));
        $bug->setLength($path->countPlaces());
        $bug->setTask($task);
        $bug->setBugMessage('You added an out-of-stock product into cart! Can not checkout');
        $entityManager->persist($bug);

        $entityManager->flush();

        $this->consumeMessages();

        $entityManager->refresh($bug);

        /** @var Bug[] $bugs */
        $bugs = $entityManager->getRepository(Bug::class)->findBy(['task' => $task->getId()]);

        $this->assertEquals(1, count($bugs));
        $this->assertEquals('You added an out-of-stock product into cart! Can not checkout', $bugs[0]->getBugMessage());
        if ($reducer !== 'random') {
            $this->assertEquals(serialize($expectedPath), $bugs[0]->getPath());
            $this->assertEquals($expectedPath->countPlaces(), $bugs[0]->getLength());
        } else {
            $this->assertLessThanOrEqual($expectedPath->countPlaces(), $bugs[0]->getLength());
        }

        if ($reporter === 'email') {
            $command = $this->application->find('swiftmailer:spool:send');
            $commandTester = new CommandTester($command);
            $commandTester->execute([
                'command' => $command->getName(),
            ]);

            $output = $commandTester->getDisplay();
            $this->assertContains('1 emails sent', $output);
        } elseif ($reporter === 'hipchat') {
            $this->hasHipchatMessages();
        }
    }

    public function consumeMessageData()
    {
        return [
            [
                [
                    [null, 'viewAnyCategoryFromHome', 'addFromCategory', 'checkoutFromCategory'],
                    [null, ['category' => 57], ['product' => 49], []],
                    [['home'], ['category'], ['category'], ['checkout']]
                ],
                'queued-loop',
                'email',
                [
                    [null, 'viewAnyCategoryFromHome', 'addFromCategory', 'checkoutFromCategory'],
                    [null, ['category' => 57], ['product' => 49], []],
                    [['home'], ['category'], ['category'], ['checkout']]
                ],
            ],
            [
                [
                    [null, 'viewAnyCategoryFromHome', 'viewProductFromCategory', 'addFromProduct', 'checkoutFromProduct', 'viewCartFromCheckout', 'viewProductFromCart', 'viewAnyCategoryFromProduct', 'addFromCategory', 'checkoutFromCategory'],
                    [null, ['category' => '34'], ['product' => '48'], [], [], [], ['product' => '48'], ['category' => '57'], ['product' => '49'], []],
                    [['home'], ['category'], ['product'], ['product'], ['checkout'], ['cart'], ['product'], ['category'], ['category'], ['checkout']],
                ],
                'queued-loop',
                'hipchat',
                [
                    [null, 'viewAnyCategoryFromHome', 'viewProductFromCategory', 'viewAnyCategoryFromProduct', 'addFromCategory', 'checkoutFromCategory'],
                    [null, ['category' => '34'], ['product' => '48'], ['category' => '57'], ['product' => '49'], []],
                    [['home'], ['category'], ['product'], ['category'], ['category'], ['checkout']],
                ]
            ],
            [
                [
                    [null, 'addFromHome', 'viewAnyCategoryFromHome', 'addFromCategory', 'checkoutFromCategory'],
                    [null, ['product' => '40'], ['category' => '57'], ['product' => '49'], []],
                    [['home'], ['home'], ['category'], ['category'], ['checkout']],
                ],
                'greedy',
                'email',
                [
                    [null, 'viewAnyCategoryFromHome', 'addFromCategory', 'checkoutFromCategory'],
                    [null, ['category' => '57'], ['product' => '49'], []],
                    [['home'], ['category'], ['category'], ['checkout']],
                ]
            ],
            [
                [
                    [null, 'viewAnyCategoryFromHome', 'addFromCategory', 'viewCartFromCategory', 'backToHomeFromCart', 'viewAnyCategoryFromHome', 'viewProductFromCategory', 'addFromProduct', 'checkoutFromProduct'],
                    [null, ['category' => '33'], ['product' => '31'], [], [], ['category' => '57'], ['product' => '49'], [], []],
                    [['home'], ['category'], ['category'], ['cart'], ['home'], ['category'], ['product'], ['product'], ['checkout']],
                ],
                'binary',
                'hipchat',
                [
                    [null, 'viewAnyCategoryFromHome', 'viewProductFromCategory', 'addFromProduct', 'checkoutFromProduct'],
                    [null, ['category' => '57'], ['product' => '49'], [], []],
                    [['home'], ['category'], ['product'], ['product'], ['checkout']],
                ]
            ],
            [
                [
                    [null, 'viewAnyCategoryFromHome', 'viewOtherCategory', 'addFromCategory', 'viewOtherCategory', 'viewProductFromCategory', 'backToHomeFromProduct', 'checkoutFromHome'],
                    [null, ['category' => '34'], ['category' => '57'], ['product' => '49'], ['category' => '34'], ['product' => '48'], [], []],
                    [['home'], ['category'], ['category'], ['category'], ['category'], ['product'], ['home'], ['checkout']],
                ],
                'binary',
                'email',
                [
                    [null, 'viewAnyCategoryFromHome', 'viewOtherCategory', 'addFromCategory', 'checkoutFromCategory'],
                    [null, ['category' => '34'], ['category' => '57'], ['product' => '49'], []],
                    [['home'], ['category'], ['category'], ['category'], ['checkout']],
                ]
            ],
            [
                [
                    [null, 'viewCartFromHome', 'backToHomeFromCart', 'viewAnyCategoryFromHome', 'addFromCategory', 'viewOtherCategory', 'viewOtherCategory', 'checkoutFromCategory'],
                    [null, [], [], ['category' => '57'], ['product' => '49'], ['category' => '25_28'], ['category' => '20'], []],
                    [['home'], ['cart'], ['home'], ['category'], ['category'], ['category'], ['category'], ['checkout']],
                ],
                'greedy',
                'hipchat',
                [
                    [null, 'viewAnyCategoryFromHome', 'addFromCategory', 'checkoutFromCategory'],
                    [null, ['category' => '57'], ['product' => '49'], []],
                    [['home'], ['category'], ['category'], ['checkout']],
                ]
            ],
            [
                [
                    [null, 'checkoutFromHome', 'backToHomeFromCheckout', 'viewAnyCategoryFromHome', 'addFromCategory', 'viewProductFromCategory', 'viewAnyCategoryFromProduct', 'addFromCategory', 'viewCartFromCategory', 'viewProductFromCart', 'viewAnyCategoryFromProduct', 'checkoutFromCategory'],
                    [null, [], [], ['category' => '20'], ['product' => '46'], ['product' => '33'], ['category' => '57'], ['product' => '49'], [], ['product' => '46'], ['category' => '57'], []],
                    [['home'], ['checkout'], ['home'], ['category'], ['category'], ['product'], ['category'], ['category'], ['cart'], ['product'], ['category'], ['checkout']],
                ],
                'loop',
                'email',
                [
                    [null, 'viewAnyCategoryFromHome', 'viewProductFromCategory', 'viewAnyCategoryFromProduct', 'addFromCategory', 'checkoutFromCategory'],
                    [null, ['category' => '20'], ['product' => '33'], ['category' => '57'], ['product' => '49'], []],
                    [['home'], ['category'], ['product'], ['category'], ['category'], ['checkout']],
                ]
            ],
            [
                [
                    [null, 'viewAnyCategoryFromHome', 'viewProductFromCategory', 'viewAnyCategoryFromProduct', 'viewOtherCategory', 'viewOtherCategory', 'viewProductFromCategory', 'addFromProduct', 'viewAnyCategoryFromProduct', 'addFromCategory', 'viewOtherCategory', 'viewOtherCategory', 'addFromCategory', 'checkoutFromCategory'],
                    [null, ['category' => '20_27'], ['product' => '41'], ['category' => '24'], ['category' => '17'], ['category' => '24'], ['product' => '28'], [], ['category' => '57'], ['product' => '49'], ['category' => '20_27'], ['category' => '20'], ['product' => '33'], []],
                    [['home'], ['category'], ['product'], ['category'], ['category'], ['category'], ['product'], ['product'], ['category'], ['category'], ['category'], ['category'], ['category'], ['checkout']],
                ],
                'queued-loop',
                'hipchat',
                [
                    [null, 'viewAnyCategoryFromHome', 'viewProductFromCategory', 'viewAnyCategoryFromProduct', 'addFromCategory', 'checkoutFromCategory'],
                    [null, ['category' => '20_27'], ['product' => '41'], ['category' => '57'], ['product' => '49'], []],
                    [['home'], ['category'], ['product'], ['category'], ['category'], ['checkout']],
                ]
            ],
            [
                [
                    [null, 'viewAnyCategoryFromHome', 'addFromCategory', 'viewOtherCategory', 'viewProductFromCategory', 'backToHomeFromProduct', 'checkoutFromHome'],
                    [null, ['category' => '57'], ['product' => '49'], ['category' => '34'], ['product' => '48'], [], []],
                    [['home'], ['category'], ['category'], ['category'], ['product'], ['home'], ['checkout']],
                ],
                'greedy',
                'email',
                [
                    [null, 'viewAnyCategoryFromHome', 'addFromCategory', 'checkoutFromCategory'],
                    [null, ['category' => '57'], ['product' => '49'], []],
                    [['home'], ['category'], ['category'], ['checkout']],
                ]
            ],
            [
                [
                    [null, 'viewAnyCategoryFromHome', 'viewOtherCategory', 'addFromCategory', 'viewProductFromCategory', 'backToHomeFromProduct', 'checkoutFromHome'],
                    [null, ['category' => '18'], ['category' => '57'], ['product' => '49'], ['product' => '48'], [], []],
                    [['home'], ['category'], ['category'], ['category'], ['product'], ['home'], ['checkout']],
                ],
                'random',
                'hipchat',
                [
                    [null, 'viewAnyCategoryFromHome', 'viewOtherCategory', 'addFromCategory', 'viewProductFromCategory', 'backToHomeFromProduct', 'checkoutFromHome'],
                    [null, ['category' => '18'], ['category' => '57'], ['product' => '49'], ['product' => '48'], [], []],
                    [['home'], ['category'], ['category'], ['category'], ['product'], ['home'], ['checkout']],
                ]
            ],
        ];
    }

    protected function clearHipchatMessages()
    {
        exec("rm -rf {$this->cacheDir}/hipchat/");
    }

    protected function hasHipchatMessages()
    {
        return filesize("{$this->cacheDir}/hipchat/message.data") !== 0;
    }
}
