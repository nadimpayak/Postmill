<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class RateLimitValidator extends ConstraintValidator {
    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var array
     */
    private $ipWhitelist;

    public function __construct(
        EntityManagerInterface $manager,
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorage,
        array $ipWhitelist
    ) {
        $this->manager = $manager;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->ipWhitelist = $ipWhitelist;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint) {
        if ($value === null) {
            return;
        }

        if (!$constraint instanceof RateLimit) {
            throw new UnexpectedTypeException($constraint, RateLimit::class);
        }

        if (!$constraint->entityClass && !\is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        if (!$constraint->ipField && !$constraint->userField) {
            throw new ConstraintDefinitionException(
                'Either the "ip" or "user" fields must be specified'
            );
        }

        $class = $constraint->entityClass ?: \get_class($value);
        $interval = \DateInterval::createFromDateString($constraint->period);
        $time = (new \DateTime('@'.time()))->sub($interval);

        $qb = $this->manager->createQueryBuilder()
            ->select('COUNT(e)')
            ->from($class, 'e')
            ->where(sprintf('e.%s >= :timestamp', $constraint->timestampField))
            ->setParameter('timestamp', $time, Type::DATETIMETZ);

        $expr = $qb->expr()->orX();

        if ($constraint->ipField) {
            $request = $this->requestStack->getCurrentRequest();
            $ip = $request ? $request->getClientIp() : null;

            if ($ip && !IpUtils::checkIp($ip, $this->ipWhitelist)) {
                $expr->add("e.{$constraint->ipField} = :ip");

                $qb->setParameter('ip', $ip);
            }
        }

        if ($constraint->userField) {
            $token = $this->tokenStorage->getToken();
            $user = $token ? $token->getUser() : null;

            if ($user instanceof User) {
                $expr->add("e.{$constraint->userField} = :user");

                $qb->setParameter('user', $user);
            }
        }

        $qb->andWhere($expr);

        $count = $qb->getQuery()->getSingleScalarResult();

        if ($count >= $constraint->max) {
            $this->context->buildViolation($constraint->message)
                ->atPath($constraint->errorPath)
                ->setCode(RateLimit::RATE_LIMITED_ERROR)
                ->addViolation();
        }
    }
}
