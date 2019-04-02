<?php

namespace App\Repository;

use App\Entity\Submission;
use App\Repository\Submission\NoSubmissionsException;
use App\Repository\Submission\SubmissionPager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\HttpFoundation\Request;

class SubmissionRepository extends ServiceEntityRepository {
    public const SORT_HOT = 'hot';
    public const SORT_NEW = 'new';
    public const SORT_TOP = 'top';
    public const SORT_CONTROVERSIAL = 'controversial';
    public const SORT_MOST_COMMENTED = 'most_commented';

    public const TIME_DAY = 'day';
    public const TIME_WEEK = 'week';
    public const TIME_MONTH = 'month';
    public const TIME_YEAR = 'year';
    public const TIME_ALL = 'all';

    public const VALID_TIMES = [
        self::TIME_DAY,
        self::TIME_WEEK,
        self::TIME_MONTH,
        self::TIME_YEAR,
        self::TIME_ALL,
    ];

    /**
     * `$sortBy` -> ordered column name mapping.
     *
     * @var array[]
     */
    public const SORT_COLUMN_MAP = [
        self::SORT_HOT => ['ranking', 'id'],
        self::SORT_NEW => ['id'],
        self::SORT_TOP => ['net_score', 'id'],
        self::SORT_CONTROVERSIAL => ['downvotes', 'id'],
        self::SORT_MOST_COMMENTED => ['comment_count', 'id'],
    ];

    public const SORT_COLUMN_TYPES = [
        'ranking' => 'bigint',
        'id' => 'bigint',
        'net_score' => 'integer',
        'downvotes' => 'integer',
        'comment_count' => 'integer',
    ];

    private const MAX_PER_PAGE = 25;

    private const NET_SCORE_JOIN = '('.
            'SELECT submission_id, '.
                'COUNT(*) FILTER (WHERE upvote = TRUE) - '.
                    'COUNT(*) FILTER (WHERE upvote = FALSE) AS net_score '.
            'FROM submission_votes '.
            'GROUP BY submission_id'.
        ')';

    // TODO: implement actually useful controversy metric
    private const CONTROVERSIAL_JOIN = '('.
            'SELECT submission_id, COUNT(*) AS downvotes '.
            'FROM submission_votes '.
            'WHERE upvote = FALSE '.
            'GROUP BY submission_id'.
        ')';

    private const COMMENT_COUNT_JOIN = '('.
            'SELECT submission_id, COUNT(*) AS comment_count '.
            'FROM comments '.
            'GROUP BY submission_id'.
        ')';

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Submission::class);
    }

    /**
     * The amazing submission finder.
     *
     * @param string  $sortBy  One of SORT_* constants
     * @param array   $options An array with the following keys:
     *                         <ul>
     *                         <li><kbd>forums</kbd>
     *                         <li><kbd>excluded_forums</kbd>
     *                         <li><kbd>users</kbd>
     *                         <li><kbd>excluded_users</kbd>
     *                         <li><kbd>stickies</kbd> - show stickies first
     *                         <li><kbd>max_per_page</kbd>
     *                         <li><kbd>time</kbd> - One of TIME_* constants
     *                         </ul>
     * @param Request $request Request to retrieve pager options and time filter
     *                         from
     *
     * @return Submission[]|SubmissionPager
     *
     * @throws \InvalidArgumentException if $sortBy is bad
     * @throws NoSubmissionsException    if there are no submissions
     */
    public function findSubmissions(string $sortBy, array $options = [], Request $request = null) {
        $maxPerPage = $options['max_per_page'] ?? self::MAX_PER_PAGE;
        $time = $request->query->get('t', self::TIME_ALL);

        // Silently fail on invalid time
        if (!\in_array($time, self::VALID_TIMES, true)) {
            $time = self::TIME_ALL;
        }

        $rsm = $this->createResultSetMappingBuilder('s');

        $qb = $this->_em->getConnection()->createQueryBuilder()
            ->select($rsm->generateSelectClause())
            ->from('submissions', 's')
            ->setMaxResults($maxPerPage + 1);

        switch ($sortBy) {
        case self::SORT_HOT:
        case self::SORT_NEW:
            break;
        case self::SORT_TOP:
            $qb->join('s', self::NET_SCORE_JOIN, 'ns', 's.id = ns.submission_id');
            break;
        case self::SORT_CONTROVERSIAL:
            $qb->join('s', self::CONTROVERSIAL_JOIN, 'cn', 's.id = cn.submission_id');
            break;
        case self::SORT_MOST_COMMENTED:
            $qb->join('s', self::COMMENT_COUNT_JOIN, 'cc', 's.id = cc.submission_id');
            break;
        default:
            throw new \InvalidArgumentException("Sort mode '$sortBy' not implemented");
        }

        if ($time !== self::TIME_ALL) {
            $since = new \DateTime();

            $qb->andWhere('s.timestamp > :time');
            $qb->setParameter('time', $since, Type::DATETIMETZ);
        }

        switch ($time) {
        case self::TIME_ALL:
            break;
        case self::TIME_YEAR:
            $since->modify('-1 year');
            break;
        case self::TIME_MONTH:
            $since->modify('-1 month');
            break;
        case self::TIME_WEEK:
            $since->modify('-1 week');
            break;
        case self::TIME_DAY:
            $since->modify('-1 day');
            break;
        default:
            throw new \InvalidArgumentException("Time mode '$time' not implemented");
        }

        $pager = $request
            ? SubmissionPager::getParamsFromRequest($sortBy, $request)
            : [];

        if (!empty($options['stickies'])) {
            if (!$pager) {
                // Order by stickies on page 1.
                $qb->orderBy('sticky', 'DESC');
            } else {
                // Exclude all stickies from page 2 and onward, since they're
                // assumed to be on page 1. Will miss all stickies that are
                // meant to be on the next page. The solution is to not be a
                // doofus and sticky more than $maxPerPage posts.
                $qb->andWhere($qb->expr()->eq('sticky', 'false'));
            }
        }

        foreach (self::SORT_COLUMN_MAP[$sortBy] as $column) {
            $qb->addOrderBy($column, 'DESC');
        }

        if ($pager) {
            $qb->andWhere(sprintf('(%s) <= (:next_%s)',
                implode(', ', self::SORT_COLUMN_MAP[$sortBy]),
                implode(', :next_', self::SORT_COLUMN_MAP[$sortBy])
            ));

            foreach (self::SORT_COLUMN_MAP[$sortBy] as $column) {
                $qb->setParameter('next_'.$column, $pager[$column]);
            }
        }

        self::filterQuery($qb, $options);

        $results = $this->_em
            ->createNativeQuery($qb->getSQL(), $rsm)
            ->setParameters($qb->getParameters())
            ->execute();

        if ($pager && \count($results) === 0) {
            throw new NoSubmissionsException();
        }

        $submissions = new SubmissionPager($results, $maxPerPage, $sortBy);

        $this->hydrateAssociations($submissions);

        return $submissions;
    }

    private static function filterQuery(QueryBuilder $qb, array $options): void {
        if (!empty($options['forums'])) {
            /* @noinspection NotOptimalIfConditionsInspection */
            if (!empty($options['excluded_forums'])) {
                $options['forums'] = array_diff(
                    $options['forums'],
                    $options['excluded_forums']
                );
            }

            $qb->andWhere('s.forum_id IN (:forum_ids)');
            $qb->setParameter('forum_ids', $options['forums']);
        } elseif (!empty($options['excluded_forums'])) {
            $qb->andWhere('s.forum_id NOT IN (:forum_ids)');
            $qb->setParameter('forum_ids', $options['excluded_forums']);
        }

        if (!empty($options['users'])) {
            /* @noinspection NotOptimalIfConditionsInspection */
            if (!empty($options['excluded_users'])) {
                $options['users'] = array_diff(
                    $options['users'],
                    $options['excluded_users']
                );
            }

            $qb->andWhere('s.user_id IN (:user_ids)');
            $qb->setParameter('user_ids', $options['users']);
        } elseif (!empty($options['excluded_users'])) {
            $qb->andWhere('s.user_id NOT IN (:user_ids)');
            $qb->setParameter('user_ids', $options['excluded_users']);
        }
    }

    private function hydrateAssociations(iterable $submissions): void {
        if ($submissions instanceof \Traversable) {
            $submissions = iterator_to_array($submissions);
        }

        $this->_em->createQueryBuilder()
            ->select('PARTIAL s.{id}')
            ->addSelect('u')
            ->addSelect('f')
            ->from(Submission::class, 's')
            ->join('s.user', 'u')
            ->join('s.forum', 'f')
            ->where('s IN (?1)')
            ->setParameter(1, $submissions)
            ->getQuery()
            ->getResult();

        $this->_em->createQueryBuilder()
            ->select('PARTIAL s.{id}')
            ->addSelect('sv')
            ->from(Submission::class, 's')
            ->leftJoin('s.votes', 'sv')
            ->where('s IN (?1)')
            ->setParameter(1, $submissions)
            ->getQuery()
            ->getResult();

        $this->_em->createQueryBuilder()
            ->select('PARTIAL s.{id}')
            ->addSelect('PARTIAL c.{id}')
            ->from(Submission::class, 's')
            ->leftJoin('s.comments', 'c')
            ->where('s IN (?1)')
            ->setParameter(1, $submissions)
            ->getQuery()
            ->getResult();
    }
}
