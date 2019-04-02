<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NotificationRepository")
 * @ORM\Table(name="notifications")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="notification_type", type="text")
 * @ORM\DiscriminatorMap({
 *     "comment": "CommentNotification",
 *     "comment_mention": "CommentMention",
 *     "message": "MessageNotification",
 *     "submission_mention": "SubmissionMention",
 * })
 */
abstract class Notification {
    /**
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     * @ORM\Id()
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="User", inversedBy="notifications")
     *
     * @var User
     */
    private $user;

    public function __construct(User $receiver) {
        $this->user = $receiver;
    }

    abstract public function getType(): string;

    public function getId(): ?int {
        return $this->id;
    }

    public function getUser(): User {
        return $this->user;
    }
}
