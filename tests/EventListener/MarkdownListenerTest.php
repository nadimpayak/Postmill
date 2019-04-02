<?php

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\MarkdownInitEvent;
use App\EventListener\MarkdownListener;
use App\Markdown\AppExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class MarkdownListenerTest extends TestCase {
    public function testInitListenerAddsTargetBlankToPurifierConfig() {
        $user = $this->createMock(User::class);
        $user
            ->method('openExternalLinksInNewTab')
            ->willReturn(true, false);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($user);

        /* @var TokenStorageInterface|MockObject $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token);

        /* @var AppExtension|MockObject $extension */
        $extension = $this->createMock(AppExtension::class);

        $listener = new MarkdownListener($extension, $tokenStorage);

        $event = new MarkdownInitEvent([]);
        $listener->onMarkdownInit($event);
        $this->assertArrayHasKey('HTML.TargetBlank', $event->getHtmlPurifierConfig());
        $this->assertTrue($event->getHtmlPurifierConfig()['HTML.TargetBlank']);

        $event = new MarkdownInitEvent([]);
        $listener->onMarkdownInit($event);
        $this->assertArrayNotHasKey('HTML.TargetBlank', $event->getHtmlPurifierConfig());
    }
}
