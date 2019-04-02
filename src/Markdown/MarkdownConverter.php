<?php

namespace App\Markdown;

use App\Event\MarkdownCacheEvent;
use App\Event\MarkdownInitEvent;
use App\Events;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Service for converting user-inputted Markdown (Markdown) to HTML.
 */
class MarkdownConverter {
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var CacheItemPoolInterface
     */
    private $cacheItemPool;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        CacheItemPoolInterface $cacheItemPool
    ) {
        $this->dispatcher = $dispatcher;
        $this->cacheItemPool = $cacheItemPool;
    }

    public function convertToHtml(string $markdown, array $context = []): string {
        $event = new MarkdownInitEvent($context);

        $this->dispatcher->dispatch(Events::MARKDOWN_INIT, $event);

        $commonMarkEnvironment = new Environment();

        foreach ($event->getExtensions() as $extension) {
            $commonMarkEnvironment->addExtension($extension);
        }

        $commonMarkConverter = new CommonMarkConverter(
            $event->getCommonMarkConfig(),
            $commonMarkEnvironment
        );

        $purifierConfig = \HTMLPurifier_Config::create($event->getHtmlPurifierConfig());
        $purifier = new \HTMLPurifier($purifierConfig);

        $html = $commonMarkConverter->convertToHtml($markdown);
        $html = $purifier->purify($html);

        return $html;
    }

    public function convertToHtmlCached(string $markdown, array $context = []): string {
        $event = new MarkdownCacheEvent($context);

        $this->dispatcher->dispatch(Events::MARKDOWN_CACHE, $event);

        $key = sprintf(
            'cached_markdown.%s.%s',
            hash('sha256', $markdown),
            $event->getCacheKey()
        );

        $cacheItem = $this->cacheItemPool->getItem($key);

        if (!$cacheItem->isHit()) {
            $cacheItem->set($this->convertToHtml($markdown, $context));

            $this->cacheItemPool->saveDeferred($cacheItem);
        }

        return $cacheItem->get();
    }
}
