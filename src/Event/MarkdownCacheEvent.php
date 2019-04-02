<?php

namespace App\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event dispatched to build a hash key for Markdown context.
 */
final class MarkdownCacheEvent extends Event {
    /**
     * @var array
     */
    private $context;

    private $hashData = [];

    public function __construct(array $context) {
        $this->context = $context;
    }

    public function getContext(): array {
        return $this->context;
    }

    public function getCacheKey(): string {
        ksort($this->hashData);

        return hash('sha256', json_encode($this->hashData));
    }

    public function addToCacheKey(string $key, ?string $value = null): void {
        $this->hashData[$key] = $value;
    }
}
