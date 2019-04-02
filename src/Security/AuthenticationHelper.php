<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

class AuthenticationHelper {
    /**
     * @var GuardAuthenticatorHandler
     */
    private $guardHandler;

    /**
     * @var LoginAuthenticator
     */
    private $loginAuthenticator;

    /**
     * @var RememberMeServicesInterface
     */
    private $rememberMeServices;

    /**
     * @var string
     */
    private $secret;

    public function __construct(
        GuardAuthenticatorHandler $guardHandler,
        LoginAuthenticator $loginAuthenticator,
        RememberMeServicesInterface $rememberMeServices,
        string $secret
    ) {
        $this->guardHandler = $guardHandler;
        $this->loginAuthenticator = $loginAuthenticator;
        $this->rememberMeServices = $rememberMeServices;
        $this->secret = $secret;
    }

    /**
     * Programmatically set a user as logged in.
     *
     * @param User     $user
     * @param Request  $request
     * @param Response $response
     * @param string   $providerKey
     */
    public function login(User $user, Request $request, Response $response, string $providerKey): void {
        $token = $this->loginAuthenticator->createAuthenticatedToken($user, $providerKey);
        $this->guardHandler->authenticateWithToken($token, $request, $providerKey);

        $token = new RememberMeToken($user, $providerKey, $this->secret);
        $this->rememberMeServices->loginSuccess($request, $response, $token);
    }
}
