<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Entity\User;
use App\Entity\UserBlock;
use App\Form\Model\UserBlockData;
use App\Form\Model\UserData;
use App\Form\Model\UserFilterData;
use App\Form\UserBiographyType;
use App\Form\UserBlockType;
use App\Form\UserFilterType;
use App\Form\UserSettingsType;
use App\Form\UserType;
use App\Repository\ForumBanRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Security\AuthenticationHelper;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Entity("user", expr="repository.findOneOrRedirectToCanonical(username, 'username')")
 */
final class UserController extends AbstractController {
    /**
     * @var string
     */
    private $defaultLocale;

    public function __construct(string $defaultLocale) {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Show the user's profile page.
     *
     * @param User           $user
     * @param Request        $request
     * @param UserRepository $users
     *
     * @return Response
     */
    public function userPage(User $user, Request $request, UserRepository $users) {
        $nextUnixTime = $request->query->getInt('next_timestamp');

        if ($nextUnixTime) {
            $nextTimestamp = new \DateTime('@'.$nextUnixTime);
        }

        $contributions = $users->findContributions($user, $nextTimestamp ?? null);

        if ($nextUnixTime && !\count($contributions)) {
            throw $this->createNotFoundException('No such page');
        }

        return $this->render('user/user.html.twig', [
            'contributions' => $contributions,
            'user' => $user,
        ]);
    }

    /**
     * @param User $user
     * @param int  $page
     *
     * @return Response
     */
    public function submissions(User $user, int $page) {
        return $this->render('user/submissions.html.twig', [
            'submissions' => $user->getPaginatedSubmissions($page),
            'user' => $user,
        ]);
    }

    /**
     * @param User $user
     * @param int  $page
     *
     * @return Response
     */
    public function comments(User $user, int $page) {
        return $this->render('user/comments.html.twig', [
            'comments' => $user->getPaginatedComments($page),
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     *
     * @param UserRepository $users
     * @param int            $page
     * @param Request        $request
     *
     * @return Response
     */
    public function list(UserRepository $users, int $page, Request $request) {
        $filter = new UserFilterData();
        $criteria = $filter->buildCriteria();

        $form = $this->createForm(UserFilterType::class, $filter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $filter->buildCriteria();
        }

        return $this->render('user/list.html.twig', [
            'form' => $form->createView(),
            'page' => $page,
            'users' => $users->findPaginated($page, $criteria),
        ]);
    }

    public function registration(
        Request $request,
        EntityManager $em,
        AuthenticationHelper $authenticationHelper
    ) {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('front');
        }

        $data = new UserData();
        $data->setLocale($this->defaultLocale);

        $form = $this->createForm(UserType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $data->toUser();

            $em->persist($user);
            $em->flush();

            $response = $this->redirectToRoute('front');

            $authenticationHelper->login($user, $request, $response, 'main');

            $this->addFlash('success', 'flash.user_account_registered');

            return $response;
        }

        return $this->render('user/registration.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit_user", subject="user", statusCode=403)
     *
     * @param EntityManager $em
     * @param User          $user
     * @param Request       $request
     *
     * @return Response
     */
    public function editUser(EntityManager $em, User $user, Request $request) {
        $data = UserData::fromUser($user);

        $form = $this->createForm(UserType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateUser($user);

            $em->flush();

            return $this->redirectToRoute('edit_user', [
                'username' => $user->getUsername(),
            ]);
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit_user", subject="user", statusCode=403)
     *
     * @param EntityManager $em
     * @param User          $user
     * @param Request       $request
     *
     * @return Response
     */
    public function userSettings(EntityManager $em, User $user, Request $request) {
        $data = UserData::fromUser($user);

        $form = $this->createForm(UserSettingsType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateUser($user);

            $em->flush();

            $this->addFlash('success', 'flash.user_settings_updated');

            return $this->redirect($request->getUri());
        }

        return $this->render('user/settings.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit_user", subject="user", statusCode=403)
     *
     * @param EntityManager $em
     * @param User          $user
     * @param Request       $request
     *
     * @return Response
     */
    public function editBiography(EntityManager $em, User $user, Request $request) {
        $data = UserData::fromUser($user);

        $form = $this->createForm(UserBiographyType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateUser($user);

            $em->flush();

            $this->addFlash('success', 'flash.user_biography_updated');

            return $this->redirect($request->getUri());
        }

        return $this->render('user/edit_biography.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit_user", subject="user", statusCode=403)
     *
     * @param User $user
     * @param int $page
     *
     * @return Response
     */
    public function blockList(User $user, int $page) {
        return $this->render('user/block_list.html.twig', [
            'blocks' => $user->getPaginatedBlocks($page),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Entity("blockee", expr="repository.findOneOrRedirectToCanonical(username, 'username')")
     *
     * @param User          $blockee
     * @param Request       $request
     * @param EntityManager $em
     *
     * @return Response
     */
    public function block(User $blockee, Request $request, EntityManager $em) {
        /* @var User $blocker */
        $blocker = $this->getUser();

        if ($blocker->isBlocking($blockee)) {
            throw $this->createNotFoundException('The user is already blocked');
        }

        $data = new UserBlockData();

        $form = $this->createForm(UserBlockType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $block = $data->toBlock($blocker, $blockee);

            $em->persist($block);
            $em->flush();

            $this->addFlash('success', 'flash.user_blocked');

            return $this->redirectToRoute('user_block_list', [
                'username' => $blocker->getUsername(),
            ]);
        }

        return $this->render('user/block.html.twig', [
            'blockee' => $blockee,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Security("user === block.getBlocker()", statusCode=403)
     *
     * @param UserBlock     $block
     * @param EntityManager $em
     * @param Request       $request
     *
     * @return Response
     */
    public function unblock(UserBlock $block, EntityManager $em, Request $request) {
        $this->validateCsrf('unblock', $request->request->get('token'));

        $em->remove($block);
        $em->flush();

        $this->addFlash('success', 'flash.user_unblocked');

        return $this->redirectToRoute('user_block_list', [
            'username' => $this->getUser()->getUsername(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     *
     * @param int $page
     *
     * @return Response
     */
    public function notifications(int $page) {
        /* @var User $user */
        $user = $this->getUser();

        return $this->render('user/notifications.html.twig', [
            'notifications' => $user->getPaginatedNotifications($page),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     *
     * @param Request                $request
     * @param NotificationRepository $nr
     * @param EntityManager          $em
     * @param string                 $_format
     *
     * @return Response
     */
    public function clearNotifications(Request $request, NotificationRepository $nr, EntityManager $em, string $_format) {
        $this->validateCsrf('clear_notifications', $request->request->get('token'));

        $user = $this->getUser();
        $max = $request->query->getInt('max', null);

        $nr->clearNotifications($user, $max);
        $em->flush();

        if ($_format === 'json') {
            return $this->json(['message' => 'The notifications were successfully cleared.']);
        }

        $this->addFlash('notice', 'flash.notifications_cleared');

        return $this->redirectToRoute('notifications');
    }

    /**
     * @IsGranted("ROLE_USER")
     *
     * @param Request                $request
     * @param NotificationRepository $nr
     * @param EntityManager          $em
     * @param string                 $_format
     *
     * @return Response
     */
    public function clearNotification(Request $request, NotificationRepository $nr, EntityManager $em, string $_format) {
        $this->validateCsrf('clear_notification', $request->request->get('token'));

        $user = $this->getUser();
        $notificationId = $request->query->getInt('id', null);

        $nr->clearNotification($user, $notificationId);
        $em->flush();

        if ($_format === 'json') {
            return $this->json(['message' => 'The notification was successfully cleared.']);
        }

        $this->addFlash('notice', 'flash.notification_cleared');

        return $this->redirectToRoute('notifications');
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     *
     * @param Request       $request
     * @param User          $user
     * @param EntityManager $em
     * @param bool          $trusted
     *
     * @return Response
     */
    public function markAsTrusted(Request $request, User $user, EntityManager $em, bool $trusted) {
        $this->validateCsrf('mark_trusted', $request->request->get('token'));

        $user->setTrusted($trusted);
        $em->flush();

        $this->addFlash('success', $trusted ? 'flash.user_marked_trusted' : 'flash.user_marked_untrusted');

        return $this->redirectToRoute('user', [
            'username' => $user->getUsername(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     *
     * @param User               $user
     * @param ForumBanRepository $repository
     * @param int                $page
     *
     * @return Response
     */
    public function listForumBans(User $user, ForumBanRepository $repository, int $page) {
        return $this->render('user/forum_bans.html.twig', [
            'bans' => $repository->findActiveBansByUser($user, $page),
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit_user", subject="user", statusCode=403)
     *
     * @param User $user
     * @param int  $page
     *
     * @return Response
     */
    public function hiddenForums(User $user, int $page) {
        return $this->render('user/hidden_forums.html.twig', [
            'forums' => $user->getPaginatedHiddenForums($page),
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit_user", subject="user", statusCode=403)
     *
     * @param EntityManager $em
     * @param Request       $request
     * @param User          $user
     * @param Forum         $forum
     * @param bool          $hide
     *
     * @return Response
     */
    public function hideForum(EntityManager $em, Request $request, User $user, Forum $forum, bool $hide) {
        $this->validateCsrf('hide_forum', $request->request->get('token'));

        if ($hide) {
            $user->hideForum($forum);
        } else {
            $user->unhideForum($forum);
        }

        $em->flush();

        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirectToRoute('hidden_forums', [
            'username' => $this->getUser()->getUsername(),
        ]);
    }
}
