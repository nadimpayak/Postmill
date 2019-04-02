<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MessageThreadRepository")
 * @ORM\Table(name="message_threads")
 */
class MessageThread {
    /**
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue()
     * @ORM\Id()
     */
    private $id;

    /**
     * @ORM\JoinTable(name="message_thread_participants", joinColumns={
     *     @ORM\JoinColumn(name="message_thread_id", referencedColumnName="id")
     * }, inverseJoinColumns={
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     * @ORM\ManyToMany(targetEntity="User")
     *
     * @var User[]|Collection|Selectable
     */
    private $participants;

    /**
     * @ORM\OneToMany(targetEntity="Message", mappedBy="thread",
     *     cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"timestamp": "ASC"})
     *
     * @var Message[]|Collection|Selectable
     */
    private $messages;

    public function __construct(User $sender, User ...$participants) {
        foreach ($participants as $participant) {
            if (!$participant->canBeMessagedBy($sender)) {
                throw new \DomainException('Sender cannot message one of the participants');
            }
        }

        $participants[] = $sender;

        $this->participants = new ArrayCollection($participants);
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @return User[]|Collection|Selectable
     */
    public function getParticipants(): Collection {
        return $this->participants;
    }

    public function userIsParticipant($user): bool {
        return $this->participants->contains($user);
    }

    /**
     * @return Message[]|Collection|Selectable
     */
    public function getMessages(): Collection {
        return $this->messages;
    }

    public function addMessage(Message $message): void {
        if (!$this->messages->contains($message)) {
            if (!$this->userIsParticipant($message->getSender())) {
                throw new \DomainException('Sender is not allowed to participate');
            }

            $this->messages->add($message);
        }
    }

    public function removeMessage(Message $message): void {
        $this->messages->removeElement($message);
    }

    public function getTitle(): string {
        $body = $this->messages[0]->getBody();
        $firstLine = preg_replace('/^# |\R.*/', '', $body);

        if (\grapheme_strlen($firstLine) <= 100) {
            return $firstLine;
        }

        return \grapheme_substr($firstLine, 0, 100).'…';
    }
}
