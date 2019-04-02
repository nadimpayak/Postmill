<?php

namespace App\Security\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class IpRateLimitedException extends AuthenticationException {
    public function getMessageKey() {
        return 'Your IP address and/or net block has been rate-limited. Please try again later.';
    }
}
