<?php

namespace App\Event;

use League\CommonMark\Extension\Extension;
use Symfony\Component\EventDispatcher\Event;

final class MarkdownInitEvent extends Event {
    /**
     * @var string[]
     */
    private $context;

    /**
     * @var Extension[]
     */
    private $extensions = [];

    private $commonMarkConfig = [];

    private $htmlPurifierConfig = [];

    public function __construct(array $context) {
        $this->context = $context;
    }

    /**
     * @return string[]
     */
    public function getContext(): array {
        return $this->context;
    }

    /**
     * @return Extension[]
     */
    public function getExtensions(): array {
        return $this->extensions;
    }

    public function addExtension(Extension $extension): void {
        $this->extensions[] = $extension;
    }

    public function getCommonMarkConfig(): array {
        return $this->commonMarkConfig;
    }

    public function addCommonMarkConfig(array $config): void {
        $this->commonMarkConfig = array_replace($this->commonMarkConfig, $config);
    }

    public function getHtmlPurifierConfig(): array {
        return $this->htmlPurifierConfig;
    }

    public function addHtmlPurifierConfig(array $config): void {
        $this->htmlPurifierConfig = array_replace($this->htmlPurifierConfig, $config);
    }
}
