<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * Class ArticleManager
 *
 * Manager for article management
 *
 * @package App\Manager
 */
class ArticleManager
{
    private SluggerInterface $slugger;
    private ErrorManager $errorManager;
    private EntityManagerInterface $entityManager;
    private HtmlSanitizerInterface $htmlSanitizer;

    public function __construct(
        SluggerInterface $slugger,
        ErrorManager $errorManager,
        EntityManagerInterface $entityManager,
        HtmlSanitizerInterface $htmlSanitizer
    ) {
        $this->slugger = $slugger;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
        $this->htmlSanitizer = $htmlSanitizer;
    }

    /**
     * Get all articles
     *
     * @return array<Article> The articles
     */
    public function getAllArticles(): array
    {
        return $this->getRepository()->findBy([], ['id' => 'DESC']);
    }

    /**
     * Get published articles
     *
     * @return array<Article> The published articles
     */
    public function getPublicArticles(): array
    {
        return $this->getRepository()->findBy(['status' => 'published'], ['publish_time' => 'DESC']);
    }

    /**
     * Get article by ID
     *
     * @param int $id The article ID
     *
     * @return Article|null The article
     */
    public function getArticle(int $id): ?Article
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Get article by slug
     *
     * @param string $slug The article slug
     *
     * @return Article|null The article
     */
    public function getArticleBySlug(string $slug): ?Article
    {
        return $this->getRepository()->findOneBy(['slug' => $slug]);
    }

    /**
     * Create new article
     *
     * @param string $title The article title
     * @param string $content The article content
     * @param string $status The article status
     *
     * @return void
     */
    public function createArticle(string $title, string $content, string $status): void
    {
        $article = new Article();
        $baseSlug = $this->slugger->slug($title)->lower();
        $slug = $this->makeUniqueSlug($baseSlug);

        // sanitize content
        $cleanContent = $this->htmlSanitizer->sanitize($content);

        $article->setTitle($title)
            ->setSlug($slug)
            ->setContent($cleanContent)
            ->setStatus($status)
            ->setPublishTime(new DateTime());

        try {
            $this->entityManager->persist($article);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'Error creating article: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update existing article
     *
     * @param Article $article The article to update
     * @param string $title The article title
     * @param string $content The article content
     * @param string $status The article status
     *
     * @return void
     */
    public function updateArticle(Article $article, string $title, string $content, string $status): void
    {
        $baseSlug = $this->slugger->slug($title)->lower();
        $slug = $this->makeUniqueSlug($baseSlug, $article->getId());

        // sanitize content
        $cleanContent = $this->htmlSanitizer->sanitize($content);

        $article->setTitle($title);
        $article->setSlug($slug);
        $article->setStatus($status);
        $article->setContent($cleanContent);

        // update edited time
        $article->setEditedTime(new DateTime());

        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'Error updating article: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete article
     *
     * @param Article $article The article to delete
     *
     * @return void
     */
    public function deleteArticle(Article $article): void
    {
        try {
            $this->entityManager->remove($article);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'Error deleting article: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Make unique slug
     *
     * @param string $slug The slug to check
     * @param int|null $excludeId The ID to exclude (for updates)
     *
     * @return string The unique slug
     */
    private function makeUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;
        $repository = $this->getRepository();

        while (true) {
            $existing = $repository->findOneBy(['slug' => $slug]);

            // if no article found with this slug, it's unique
            if (!$existing) {
                return $slug;
            }

            // if an article is found, but it's the SAME article we are updating, it's fine
            if ($excludeId !== null && $existing->getId() === $excludeId) {
                return $slug;
            }

            // otherwise, it's a conflict. Increment and try again
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }

    /**
     * Get article repository
     *
     * @return ArticleRepository The article repository
     */
    private function getRepository(): ArticleRepository
    {
        return $this->entityManager->getRepository(Article::class);
    }
}
