<?php

namespace App\Tests\Fixtures;

use App\Entity\Message;
use App\Entity\MessageThread;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadExampleMessages extends AbstractFixture implements DependentFixtureInterface {
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager) {
        /** @noinspection PhpParamsInspection */
        $thread = new MessageThread(
            $this->getReference('user-zach'),
            $this->getReference('user-emma')
        );

        $thread->addMessage(new Message(
            $thread,
            $this->getReference('user-zach'),
            'This is a message. There are many like it, but this one originates from a fixture.',
            '192.168.0.4'
        ));

        $thread->addMessage(new Message(
            $thread,
            $this->getReference('user-emma'),
            'This is a reply to the message originating from a fixture.',
            '192.168.0.3'
        ));

        $manager->persist($thread);
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [LoadExampleUsers::class];
    }
}
