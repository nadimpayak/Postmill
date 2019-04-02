<?php

namespace App\Utils;

use Symfony\Component\Cache\Adapter\AdapterInterface;

final class IpRateLimitFactory {
    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $ipWhitelist;

    public function __construct(AdapterInterface $cache, array $ipWhitelist) {
        $this->cache = $cache;
        $this->ipWhitelist = $ipWhitelist;
    }

    public function create(string $prefix, int $maxHits, string $interval): IpRateLimit {
        return new IpRateLimit(
            $this->cache,
            $this->ipWhitelist,
            $prefix,
            $maxHits,
            \DateInterval::createFromDateString($interval)
        );
    }
}
