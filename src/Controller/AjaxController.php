<?php

namespace App\Controller;

use App\Markdown\MarkdownConverter;
use Embed\Embed;
use Embed\Exceptions\InvalidUrlException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Helpers for Ajax-related stuff.
 *
 * @IsGranted("ROLE_USER")
 */
class AjaxController {
    /**
     * JSON action for retrieving link titles.
     *
     * - 200 - Found a title
     * - 400 - Bad URL
     * - 404 - No title found
     *
     * @param Request $request
     *
     * @return Response
     */
    public function fetchTitle(Request $request) {
        $url = $request->request->get('url');
        try {
            $title = Embed::create($url)->getTitle();

            if (!strlen($title)) {
                return new JsonResponse(null, 404);
            }

            return new JsonResponse(['title' => $title]);
        } catch (InvalidUrlException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function markdownPreview(Request $request, MarkdownConverter $converter) {
        $markdown = $request->request->get('markdown', '');

        if (!\is_string($markdown)) {
            throw new BadRequestHttpException();
        }

        return new Response($converter->convertToHtml($markdown));
    }
}
