<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="messages")
 */
class Message {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var UuidInterface
     */
    private $id;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="MessageThread", inversedBy="messages", cascade={"persist"})
     *
     * @var MessageThread
     */
    private $thread;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @var User
     */
    private $sender;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $body;

    /**
     * @ORM\Column(type="datetimetz")
     *
     * @var \DateTime
     */
    private $timestamp;

    /**
     * @ORM\Column(type="inet", nullable=true)
     *
     * @var string|null
     */
    private $ip;

    /**
     * @ORM\OneToMany(targetEntity="MessageNotification", mappedBy="message", cascade={"remove"})
     */
    private $notifications;

    public function __construct(MessageThread $thread, User $sender, string $body, ?string $ip) {
        if ($ip !== null && !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('$ip must be valid IP address or NULL');
        }

        $this->id = Uuid::uuid4();
        $this->thread = $thread;
        $this->sender = $sender;
        $this->body = $body;
        $this->ip = $sender->isTrustedOrAdmin() ? null : $ip;
        $this->timestamp = new \DateTime('@'.time());
        $this->notify();

        $thread->addMessage($this);
    }

    public function getId(): UuidInterface {
        return $this->id;
    }

    public function getThread(): MessageThread {
        return $this->thread;
    }

    public function getSender(): User {
        return $this->sender;
    }

    public function getBody(): string {
        return $this->body;
    }

    public function getTimestamp(): \DateTime {
        return $this->timestamp;
    }

    public function getIp(): ?string {
        return $this->ip;
    }

    private function notify() {
        foreach ($this->thread->getParticipants() as $user) {
            if ($user === $this->sender || $user->isBlocking($this->sender)) {
                // don't notify self or users blocking you
                continue;
            }

            $user->sendNotification(new MessageNotification($user, $this));
        }
    }
}
