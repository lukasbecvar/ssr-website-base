<?php

namespace App\Controller\Admin;

use App\Util\AppUtil;
use App\Entity\Article;
use App\Form\ArticleFormType;
use App\Manager\ErrorManager;
use App\Manager\ArticleManager;
use App\Annotation\CsrfProtection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ArticleController
 *
 * Controller for managing articles (Admin)
 *
 * @package App\Controller\Admin
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
     * List all articles
     *
     * @return Response The response with list of articles
     */
    #[Route('/admin/articles', methods: ['GET'], name: 'admin_articles_list')]
    public function list(): Response
    {
        return $this->render('admin/article/list.html.twig', [
            'articles' => $this->articleManager->getAllArticles(),
            'page_title' => 'Articles List'
        ]);
    }

    /**
     * Create new article
     *
     * @param Request $request The request object
     *
     * @return Response The response with new article form
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/admin/articles/new', methods: ['GET', 'POST'], name: 'admin_articles_new')]
    public function new(Request $request): Response
    {
        // init article form
        $article = new Article();
        $form = $this->createForm(ArticleFormType::class, $article);
        $form->handleRequest($request);

        // check if form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            $this->articleManager->createArticle(
                $article->getTitle(),
                $article->getContent(),
                $article->getStatus()
            );

            // redirect back to list
            return $this->redirectToRoute('admin_articles_list');
        }

        // render form
        return $this->render('admin/article/editor.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'New Article'
        ]);
    }

    /**
     * Edit article
     *
     * @param Request $request The request object
     *
     * @return Response The response with edit article form
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/admin/articles/edit', methods: ['GET', 'POST'], name: 'admin_articles_edit')]
    public function edit(Request $request): Response
    {
        // get article id from query string
        $id = (int) $this->appUtil->getQueryString('id', $request);
        if ($id == null) {
            $this->errorManager->handleError(
                'Invalid request: id is missing',
                Response::HTTP_BAD_REQUEST
            );
        }

        // get article
        $article = $this->articleManager->getArticle($id);
        if (!$article) {
            $this->errorManager->handleError(
                'Article not found',
                Response::HTTP_NOT_FOUND
            );
        }

        // init form
        $form = $this->createForm(ArticleFormType::class, $article);
        $form->handleRequest($request);

        // check if form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // update article
            $this->articleManager->updateArticle(
                $article,
                $article->getTitle(),
                $article->getContent(),
                $article->getStatus()
            );

            // redirect back to list
            return $this->redirectToRoute('admin_articles_list');
        }

        // render form
        return $this->render('admin/article/editor.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Edit Article'
        ]);
    }

    /**
     * Delete article by id
     *
     * @param Request $request The request object
     *
     * @return Response The response redirecting to list of articles
     */
    #[Route('/admin/articles/delete', methods: ['POST'], name: 'admin_articles_delete')]
    public function delete(Request $request): Response
    {
        // get article id from query string
        $id = (int) $this->appUtil->getQueryString('id', $request);
        if ($id == null) {
            $this->errorManager->handleError(
                'Invalid request: id is missing',
                Response::HTTP_BAD_REQUEST
            );
        }

        // get article
        $article = $this->articleManager->getArticle($id);

        // delete article
        if ($article) {
            $this->articleManager->deleteArticle($article);
        }

        // redirect back to list
        return $this->redirectToRoute('admin_articles_list');
    }
}
