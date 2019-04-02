<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers \App\Controller\UserController
 */
class UserControllerTest extends WebTestCase {
    public function testCannotSignUpWithPasswordLongerThan72Characters() {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/registration');

        $password = str_repeat('a', 73);

        $form = $crawler->selectButton('user[submit]')->form([
            'user[username]' => 'random4',
            'user[password][first]' => $password,
            'user[password][second]' => $password,
            'user[verification]' => 'bypass',
        ]);

        $crawler = $client->submit($form);

        $this->assertContains(
            'This value is too long. It should have 72 characters or less.',
            $crawler->filter('.form__error')->text()
        );
    }

    public function testCanReceiveSubmissionNotifications() {
        $client = $this->createEmmaClient();
        $crawler = $client->request('GET', '/f/cats/3');

        $form = $crawler->selectButton('comment[submit]')->form([
            'comment[comment]' => 'You will be notified about this comment.',
        ]);

        $client->submit($form);

        $client = $this->createZachClient();
        $crawler = $client->request('GET', '/notifications');

        $this->assertContains(
            'You will be notified about this comment.',
            $crawler->filter('.comment__body')->text()
        );
    }

    public function testCanReceiveCommentNotifications() {
        $client = $this->createEmmaClient();
        $crawler = $client->request('GET', '/f/cats/3/-/comment/3/');

        $form = $crawler->selectButton('comment[submit]')->form([
            'comment[comment]' => 'You will be notified about this comment.',
        ]);

        $client->submit($form);

        $client = $this->createZachClient();
        $crawler = $client->request('GET', '/notifications');

        $this->assertContains(
            'You will be notified about this comment.',
            $crawler->filter('.comment__body')->text()
        );
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    private function createEmmaClient() {
        $client = $this->createClient([], [
            'PHP_AUTH_USER' => 'emma',
            'PHP_AUTH_PW' => 'goodshit',
        ]);

        $client->followRedirects();

        return $client;
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    private function createZachClient() {
        $client = $this->createClient([], [
            'PHP_AUTH_USER' => 'zach',
            'PHP_AUTH_PW' => 'example2',
        ]);

        $client->followRedirects();

        return $client;
    }
}
