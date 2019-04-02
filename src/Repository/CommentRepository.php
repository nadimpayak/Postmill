<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Forum;
use App\Entity\Submission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CommentRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @param Submission|null $submission
     * @param int|null        $id
     *
     * @return Comment
     *
     * @throws NotFoundHttpException if no such comment
     */
    public function findOneBySubmissionAndIdOr404(
        ?Submission $submission,
        ?int $id
    ): ?Comment {
        if (!$submission || !$id) {
            return null;
        }

        $comment = $this->findOneBy(['submission' => $submission, 'id' => $id]);

        if (!$comment instanceof Comment) {
            throw new NotFoundHttpException('No such comment');
        }

        return $comment;
    }

    /**
     * @param int $page
     * @param int $maxPerPage
     *
     * @return Pagerfanta|Comment[]
     */
    public function findRecentPaginated(int $page, int $maxPerPage = 25) {
        $query = $this->createQueryBuilder('c')
            ->where('c.softDeleted = FALSE')
            ->orderBy('c.id', 'DESC');

        $pager = new Pagerfanta(new DoctrineORMAdapter($query, false, false));
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        $this->hydrateComments(\iterator_to_array($pager));

        return $pager;
    }

    /**
     * @param Forum $forum
     * @param int $page
     * @param int $maxPerPage
     *
     * @return Pagerfanta|Comment[]
     */
    public function findRecentPaginatedInForum(Forum $forum, int $page, int $maxPerPage = 25) {
        $query = $this->createQueryBuilder('c')
            ->join('c.submission', 's')
            ->where('s.forum = :forum')
            ->setParameter('forum', $forum)
            ->andWhere('c.softDeleted = FALSE')
            ->orderBy('c.id', 'DESC');

        $pager = new Pagerfanta(new DoctrineORMAdapter($query, false, false));
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        $this->hydrateComments(\iterator_to_array($pager));

        return $pager;
    }

    private function hydrateComments(array $comments): void {
        // hydrate parent and parent's author
        $this->createQueryBuilder('c')
            ->select('PARTIAL c.{id}')
            ->addSelect('p')
            ->addSelect('pu')
            ->leftJoin('c.parent', 'p')
            ->leftJoin('p.user', 'pu')
            ->where('c IN (?1)')
            ->setParameter(1, $comments)
            ->getQuery()
            ->execute();

        // hydrate comment author/submission/submission author/forum
        $this->createQueryBuilder('c')
            ->select('PARTIAL c.{id}')
            ->addSelect('cu')
            ->addSelect('s')
            ->addSelect('su')
            ->addSelect('f')
            ->join('c.user', 'cu')
            ->join('c.submission', 's')
            ->join('s.user', 'su')
            ->join('s.forum', 'f')
            ->where('c IN (?1)')
            ->setParameter(1, $comments)
            ->getQuery()
            ->execute();

        // hydrate votes
        $this->createQueryBuilder('c')
            ->select('PARTIAL c.{id}')
            ->addSelect('cv')
            ->leftJoin('c.votes', 'cv')
            ->where('c IN (?1)')
            ->setParameter(1, $comments)
            ->getQuery()
            ->execute();

        // hydrate children (for count only)
        $this->createQueryBuilder('c')
            ->select('PARTIAL c.{id}')
            ->addSelect('PARTIAL r.{id}')
            ->leftJoin('c.children', 'r')
            ->where('c IN (?1)')
            ->setParameter(1, $comments)
            ->getQuery()
            ->execute();
    }
}
