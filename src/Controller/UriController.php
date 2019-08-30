<?php

namespace App\Controller;

use App\Factory\PutUriRequestFactory;
use App\Service\BasicAuthentication;
use App\Service\UriManager;
use App\Struct\GetUriRequest;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UriController extends AbstractController
{
    /**
     * @Route("/{short_code}", methods={"GET"})
     * @param Request $request
     * @param UriManager $manager
     *
     * @return RedirectResponse
     */
    public function getUri(Request $request, UriManager $manager): RedirectResponse
    {
        $shortCode = $request->attributes->get('short_code');
        if ($shortCode === null) {
            return $this->createRedirectResponseTo('/');
        }

        try {
            $uri = $manager->getUri(
                new GetUriRequest($shortCode)
            );

            if (null !== $uri) {
                return $this->createRedirectResponseTo($uri->getOriginalUrl());
            }
        } catch (NonUniqueResultException $exception) {
        }

        return $this->createRedirectResponseTo('/');
    }

    /**
     * @Route("/", methods={"PUT"})
     *
     * @param Request $request
     * @param UriManager $manager
     *
     * @return Response
     */
    public function putUri(Request $request, UriManager $manager, BasicAuthentication $authentication)
    {
        $badRequestResponse = new Response(
            Response::$statusTexts[Response::HTTP_BAD_REQUEST],
            Response::HTTP_BAD_REQUEST
        );

        $token = $request->headers->get('authorization', '');
        if (false === $authentication->validateTokenAuthentication($token)) {
            return $badRequestResponse;
        }

        try {
            $purUriRequest = (new PutUriRequestFactory)->fromDirtyRequestContent($request);
            $uriEntity     = $manager->putUri($purUriRequest);
            $statusText    = $uriEntity->getShortCode();
        } catch (Exception $exception) {
            return $badRequestResponse;
        }

        return new Response(
            $statusText,
            Response::HTTP_CREATED
        );
    }

    /**
     * Create redirect response to given uri
     *
     * @param string $uri
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function createRedirectResponseTo(string $uri): RedirectResponse
    {
        return new RedirectResponse($uri, Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * @Route("/", methods={"GET","HEAD","POST","DELETE","OPTIONS","PATCH","CONNECT","PURGE","TRACE"})
     */
    public function index(): Response
    {
        return new Response(
            Response::$statusTexts[Response::HTTP_I_AM_A_TEAPOT],
            Response::HTTP_I_AM_A_TEAPOT
        );
    }
}
