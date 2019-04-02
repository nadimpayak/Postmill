<?php

namespace App\Security;

use App\Security\Exception\IpRateLimitedException;
use App\Utils\IpRateLimit;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Guard authenticator for username/password login that does things a bit
 * differently from Symfony:
 *
 * - Rate limit IPs on login.
 *
 * @todo two-factor authentication
 * @todo login tokens stored in db so users can see where they're logged in
 * @todo rehash passwords if password_needs_rehash is true
 */
final class LoginAuthenticator extends AbstractGuardAuthenticator {
    use TargetPathTrait;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var IpRateLimit
     */
    private $rateLimit;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager,
        IpRateLimit $rateLimit,
        UserPasswordEncoderInterface $passwordEncoder,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->rateLimit = $rateLimit;
        $this->urlGenerator = $urlGenerator;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function start(Request $request, AuthenticationException $authException = null) {
        $referer = $request->headers->get('Referer');

        if ($referer && $request->isMethod('GET')) {
            // store referer for later redirection
            $this->saveTargetPath($request->getSession(), 'main', $referer);
        }

        return new RedirectResponse($this->urlGenerator->generate('login'));
    }

    public function supports(Request $request) {
        return $request->attributes->get('_route') === 'login_check' &&
            $request->isMethod('POST');
    }

    public function getCredentials(Request $request) {
        $token = new CsrfToken('authenticate', $request->request->get('_csrf_token'));

        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $ip = $request->getClientIp();

        if ($this->rateLimit->isExceeded($ip)) {
            throw new IpRateLimitedException();
        }

        return [
            'username' => $request->request->get('_username') ?? null,
            'password' => $request->request->get('_password') ?? null,
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider) {
        try {
            return $userProvider->loadUserByUsername($credentials['username']);
        } catch (UsernameNotFoundException $e) {
            throw new BadCredentialsException();
        }
    }

    public function checkCredentials($credentials, UserInterface $user) {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception) {
        $session = $request->getSession();
        $session->set(Security::AUTHENTICATION_ERROR, $exception);

        $username = $request->request->get('_username');

        if (\strlen($username) <= Security::MAX_USERNAME_LENGTH) {
            $session->set(Security::LAST_USERNAME, $username);
        }

        $this->rateLimit->increment($request->getClientIp());

        return new RedirectResponse($this->urlGenerator->generate('login'));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey) {
        $this->rateLimit->reset($request->getClientIp());

        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);

        if ($targetPath) {
            $this->removeTargetPath($request->getSession(), $providerKey);

            // redirect to referer saved when we started
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('front'));
    }

    public function supportsRememberMe() {
        return true;
    }
}
