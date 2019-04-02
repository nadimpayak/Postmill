<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Rate limit by user and IP address.
 *
 * @Annotation
 * @Target("CLASS")
 */
class RateLimit extends Constraint {
    const RATE_LIMITED_ERROR = 'bf95a6b8-f86d-4c9c-80ba-db0f8630fb27';

    protected static $errorNames = [
        self::RATE_LIMITED_ERROR => 'RATE_LIMITED_ERROR',
    ];

    public $entityClass;
    public $errorPath = null;
    public $message = 'You cannot post more. Wait a while before trying again.';
    public $max;
    public $timestampField = 'timestamp';
    public $userField = 'user';
    public $ipField = 'ip';

    /**
     * {@link \DateInterval::createFromDateString()} compatible interval.
     *
     * @var string
     */
    public $period;

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null) {
        parent::__construct($options);

        $period = \DateInterval::createFromDateString($options['period']);

        $d2 = new \DateTime('@'.time());
        $d1 = (clone $d2)->sub($period);

        if ($d2 <= $d1) {
            throw new ConstraintDefinitionException(
                'The period specified is not a valid interval'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions() {
        return ['max', 'period'];
    }

    public function getTargets() {
        return Constraint::CLASS_CONSTRAINT;
    }
}
