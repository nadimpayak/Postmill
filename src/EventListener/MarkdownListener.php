<?php

namespace App\EventListener;

use App\Entity\User;
use App\Event\MarkdownCacheEvent;
use App\Event\MarkdownInitEvent;
use App\Events;
use App\Markdown\AppExtension;
use League\CommonMark\Extension\CommonMarkCoreExtension;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Webuni\CommonMark\TableExtension\TableExtension;

/**
 * Set up the CommonMark and HTML Purifier libraries with all the default stuff
 * that we want everywhere.
 */
final class MarkdownListener implements EventSubscriberInterface {
    /**
     * @var AppExtension
     */
    private $appExtension;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        AppExtension $appExtension,
        TokenStorageInterface $tokenStorage
    ) {
        $this->appExtension = $appExtension;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents() {
        return [
            Events::MARKDOWN_INIT => ['onMarkdownInit'],
            Events::MARKDOWN_CACHE => ['onMarkdownCache'],
        ];
    }

    public function onMarkdownInit(MarkdownInitEvent $event) {
        $event->addExtension(new CommonMarkCoreExtension());
        $event->addExtension(new TableExtension());
        $event->addExtension($this->appExtension);

        $event->addCommonMarkConfig([
            'html_input' => 'escape',
        ]);

        $event->addHtmlPurifierConfig([
            // Convert non-link URLs to links.
            'AutoFormat.Linkify' => true,
            // Disable cache
            'Cache.DefinitionImpl' => null,
            // Add rel="nofollow" to outgoing links.
            'HTML.Nofollow' => true,
            // Disable embedding of external resources like images.
            'URI.DisableExternalResources' => true,
        ]);

        if ($this->shouldOpenExternalLinksInNewTab()) {
            $event->addHtmlPurifierConfig(['HTML.TargetBlank' => true]);
        }
    }

    public function onMarkdownCache(MarkdownCacheEvent $event) {
        if ($this->shouldOpenExternalLinksInNewTab()) {
            $event->addToCacheKey('open_external_links_in_new_tab');
        }
    }

    private function shouldOpenExternalLinksInNewTab() {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        return $user instanceof User && $user->openExternalLinksInNewTab();
    }
}
