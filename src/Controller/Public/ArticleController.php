<?php

namespace App\Controller\Public;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use App\Manager\ArticleManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ArticleController
 *
 * Controller for displaying articles (Public)
 *
 * @package App\Controller\Public
 */
class ArticleController extends AbstractController
{
    private AppUtil $appUtil;
    private ErrorManager $errorManager;
    private ArticleManager $articleManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager, ArticleManager $articleManager)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
        $this->articleManager = $articleManager;
    }

    /**
     * List published articles
     *
     * @return Response The response with list of articles
     */
    #[Route('/articles', methods: ['GET'], name: 'public_articles_list')]
    public function list(): Response
    {
        return $this->render('public/article/list.html.twig', [
            'articles' => $this->articleManager->getPublicArticles()
        ]);
    }

    /**
     * Show article detail
     *
     * @param Request $request The request object
     *
     * @return Response The response with article detail
     */
    #[Route('/article/detail', methods: ['GET'], name: 'public_article_detail')]
    public function detail(Request $request): Response
    {
        // get article slug from query string
        $slug = $this->appUtil->getQueryString('slug', $request);
        if ($slug == null) {
            $this->errorManager->handleError(
                'Invalid request: slug is missing',
                Response::HTTP_BAD_REQUEST
            );
        }

        // get article
        $article = $this->articleManager->getArticleBySlug($slug);

        // check if article exists and is published
        if (!$article || $article->getStatus() !== 'published') {
            $this->errorManager->handleError(
                'Article not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->render('public/article/detail.html.twig', [
            'article' => $article
        ]);
    }
}
