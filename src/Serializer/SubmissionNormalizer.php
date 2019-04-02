<?php

namespace App\Serializer;

use App\Entity\Submission;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class SubmissionNormalizer implements NormalizerInterface {
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var ObjectNormalizer
     */
    private $normalizer;

    public function __construct(
        CacheManager $cacheManager,
        ObjectNormalizer $normalizer
    ) {
        $this->cacheManager = $cacheManager;
        $this->normalizer = $normalizer;
    }

    public function normalize($object, $format = null, array $context = []): array {
        if (!$object instanceof Submission) {
            throw new \InvalidArgumentException('Expected $object to be instance of '.Submission::class);
        }

        $data = $this->normalizer->normalize($object, $format, $context);

        if (\in_array('submission:read', $context['groups'] ?? [], true)) {
            $image = $object->getImage();

            foreach (['1x', '2x'] as $size) {
                if ($image) {
                    $url = $this->cacheManager->generateUrl(
                        $image,
                        "submission_thumbnail_{$size}"
                    );
                }

                $data["thumbnail_{$size}"] = $url ?? null;
            }
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool {
        return $data instanceof Submission;
    }
}
