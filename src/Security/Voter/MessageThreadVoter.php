<?php

namespace App\Security\Voter;

use App\Entity\MessageThread;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class MessageThreadVoter extends Voter {
    const ATTRIBUTES = ['access', 'reply'];

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject) {
        return $subject instanceof MessageThread && in_array($attribute, self::ATTRIBUTES);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token) {
        if (!$subject instanceof MessageThread) {
            throw new \InvalidArgumentException('$subject must be '.MessageThread::class);
        }

        switch ($attribute) {
        case 'access':
        case 'reply':
            return $subject->userIsParticipant($token->getUser());
        default:
            throw new \LogicException('Unknown attribute '.$attribute);
        }
    }
}
