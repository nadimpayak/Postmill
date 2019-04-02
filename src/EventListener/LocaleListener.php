<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * @see https://symfony.com/doc/current/session/locale_sticky_session.html
 */
final class LocaleListener {
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TranslatorInterface|LocaleAwareInterface
     */
    private $translator;

    public function __construct(
        SessionInterface $session,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator
    ) {
        if (!$translator instanceof LocaleAwareInterface) {
            throw new \InvalidArgumentException(
                '$translator must be instance of '.LocaleAwareInterface::class
            );
        }

        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
    }

    public function onKernelRequest(GetResponseEvent $event) {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        $locale = $request->getSession()->get('_locale');

        if ($locale) {
            $request->setLocale($locale);
        }
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event) {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof User) {
            $locale = $user->getLocale();

            $this->session->set('_locale', $locale);

            $event->getRequest()->setLocale($locale);

            // Because security.interactive_login runs after kernel.request,
            // where the translator gets its locale, we must manually set the
            // locale on the translator. There is no way around this.
            $this->translator->setLocale($locale);
        }
    }

    public function postUpdate(LifecycleEventArgs $args) {
        $user = $args->getEntity();

        if ($user instanceof User) {
            $token = $this->tokenStorage->getToken();

            if ($token && $token->getUser() === $user) {
                $this->session->set('_locale', $user->getLocale());
            }
        }
    }
}
