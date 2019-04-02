<?php

namespace App\Form\Model;

use App\Entity\Message;
use App\Entity\MessageThread;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class MessageData {
    /**
     * @Assert\NotBlank()
     * @Assert\Length(max="10000")
     *
     * @var string|null
     */
    private $body;

    public function getBody() {
        return $this->body;
    }

    public function setBody($body) {
        $this->body = $body;
    }

    public function toThread(User $sender, User $receiver, string $ip = null): MessageThread {
        $thread = new MessageThread($sender, $receiver);
        $thread->addMessage($this->toMessage($thread, $sender, $ip));

        return $thread;
    }

    public function toMessage(MessageThread $thread, User $sender, string $ip = null): Message {
        return new Message($thread, $sender, $this->body, $ip);
    }
}
