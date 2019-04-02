<?php

namespace App\Tests\Entity;

use App\Entity\Message;
use App\Entity\MessageThread;
use App\Entity\User;
use App\Entity\UserBlock;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase {
    /**
     * @var User
     */
    private $sender;

    /**
     * @var User
     */
    private $receiver;

    protected function setUp() {
        $this->sender = new User('u', 'p');
        $this->receiver = new User('u', 'p');
    }

    public function testNewMessagesSendNotifications() {
        $thread = new MessageThread($this->sender, $this->receiver);

        new Message($thread, $this->sender, 'c', null);
        new Message($thread, $this->receiver, 'd', null);

        $this->assertCount(1, $this->receiver->getNotifications());
        $this->assertCount(1, $this->sender->getNotifications());
    }

    public function testNonParticipantsCannotAccessThread() {
        $thread = new MessageThread($this->sender, $this->receiver);

        $this->assertFalse($thread->userIsParticipant(new User('u', 'p')));
    }

    public function testBothParticipantsCanAccessOwnThread() {
        $thread = new MessageThread($this->sender, $this->receiver);

        $this->assertTrue($thread->userIsParticipant($this->receiver));
        $this->assertTrue($thread->userIsParticipant($this->sender));
    }

    public function testThrowsExceptionWhenStartingThreadWithBlockedUser() {
        $this->receiver->addBlock(new UserBlock($this->receiver, $this->sender, 'c'));

        $this->expectException(\DomainException::class);

        new MessageThread($this->sender, $this->receiver);
    }
}
