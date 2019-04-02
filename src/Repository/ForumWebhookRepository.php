<?php

namespace App\Repository;

use App\Entity\ForumWebhook;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class ForumWebhookRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, ForumWebhook::class);
    }
}
