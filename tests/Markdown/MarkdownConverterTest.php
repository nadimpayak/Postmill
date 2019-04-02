<?php

namespace App\Tests\Utils;

use App\Entity\User;
use App\EventListener\MarkdownListener;
use App\Markdown\AppExtension;
use App\Markdown\MarkdownConverter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class MarkdownConverterTest extends TestCase {
    private function createMarkdownConverter(bool $externalLinksOpenInNewTab) {
        $user = $this->createMock(User::class);
        $user
            ->method('openExternalLinksInNewTab')
            ->willReturn($externalLinksOpenInNewTab);

        /* @var UrlGeneratorInterface|MockObject $urlGenerator */
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($user);

        /* @var TokenStorageInterface|MockObject $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new MarkdownListener(
            new AppExtension($urlGenerator),
            $tokenStorage
        ));

        /* @var CacheItemPoolInterface|MockObject $cacheItemPool */
        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);

        return new MarkdownConverter($dispatcher, $cacheItemPool);
    }

    public function testLinksHaveNoTargetByDefault() {
        $converter = $this->createMarkdownConverter(false);

        $output = $converter->convertToHtml('[link](http://example.com)');

        $crawler = new Crawler($output);
        $crawler = $crawler->filterXPath('//p/a[not(@target)]');

        $this->assertEquals('link', $crawler->html());
    }

    public function testLinksHaveTargetWithOpenExternalLinksInNewTabOption() {
        $converter = $this->createMarkdownConverter(true);

        $output = $converter->convertToHtml('[link](http://example.com)');

        $crawler = new Crawler($output);
        $crawler = $crawler->filterXPath('//p/a[contains(@target,"_blank")]');

        $this->assertEquals('link', $crawler->html());
    }
}
