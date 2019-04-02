<?php

namespace App\Controller;

use App\Entity\ForumCategory;
use App\Form\ForumCategoryType;
use App\Form\Model\ForumCategoryData;
use App\Repository\ForumCategoryRepository;
use App\Repository\ForumRepository;
use App\Repository\SubmissionRepository;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ForumCategoryController extends AbstractController {
    public function category(
        ForumCategory $category,
        string $sortBy,
        ForumRepository $fr,
        SubmissionRepository $sr,
        Request $request
    ): Response {
        $forums = $fr->findForumsInCategory($category);

        $submissions = $sr->findSubmissions($sortBy, [
            'forums' => array_keys($forums),
        ], $request);

        return $this->render('forum_category/category.html.twig', [
            'category' => $category,
            'forums' => $forums,
            'sort_by' => $sortBy,
            'submissions' => $submissions,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     *
     * @param Request       $request
     * @param EntityManager $em
     *
     * @return Response
     */
    public function create(Request $request, EntityManager $em): Response {
        $data = new ForumCategoryData();

        $form = $this->createForm(ForumCategoryType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category = $data->toForumCategory();

            $em->persist($category);
            $em->flush();

            return $this->redirectToRoute('manage_forum_categories');
        }

        return $this->render('forum_category/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     *
     * @param ForumCategory $category
     * @param Request       $request
     * @param EntityManager $em
     *
     * @return Response
     */
    public function edit(ForumCategory $category, Request $request, EntityManager $em): Response {
        $data = new ForumCategoryData($category);

        $form = $this->createForm(ForumCategoryType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateForumCategory($category);

            $em->flush();

            return $this->redirectToRoute('manage_forum_categories');
        }

        return $this->render('forum_category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     *
     * @param ForumCategoryRepository $repository
     * @param int                     $page
     *
     * @return Response
     */
    public function manage(ForumCategoryRepository $repository, int $page) {
        $categories = $repository->findPaginated($page);

        return $this->render('forum_category/manage.html.twig', [
            'categories' => $categories,
        ]);
    }
}
