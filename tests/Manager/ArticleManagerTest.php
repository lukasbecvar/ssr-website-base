<?php

namespace App\Tests\Manager;

use DateTime;
use Exception;
use App\Entity\Article;
use App\Manager\ErrorManager;
use App\Manager\ArticleManager;
use PHPUnit\Framework\TestCase;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\UnicodeString;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * Class ArticleManagerTest
 *
 * Test cases for ArticleManager
 *
 * @package App\Tests\Manager
 */
class ArticleManagerTest extends TestCase
{
    private ArticleManager $articleManager;
    private SluggerInterface & MockObject $slugger;
    private ErrorManager & MockObject $errorManager;
    private ArticleRepository & MockObject $articleRepository;
    private EntityManagerInterface & MockObject $entityManager;
    private HtmlSanitizerInterface & MockObject $htmlSanitizer;

    protected function setUp(): void
    {
        // mock dependencies
        $this->slugger = $this->createMock(SluggerInterface::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->articleRepository = $this->createMock(ArticleRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->htmlSanitizer = $this->createMock(HtmlSanitizerInterface::class);

        // mock repository retrieval
        $this->entityManager->method('getRepository')->with(Article::class)->willReturn($this->articleRepository);

        // create article manager
        $this->articleManager = new ArticleManager(
            $this->slugger,
            $this->errorManager,
            $this->entityManager,
            $this->htmlSanitizer
        );
    }

    /**
     * Test getAllArticles
     *
     * @return void
     */
    public function testGetAllArticles(): void
    {
        $articles = [new Article(), new Article()];
        $this->articleRepository->expects($this->once())->method('findBy')->with([], ['id' => 'DESC'])->willReturn($articles);

        // call tested method
        $result = $this->articleManager->getAllArticles();

        // assert result
        $this->assertCount(2, $result);
    }

    /**
     * Test getPublicArticles
     *
     * @return void
     */
    public function testGetPublicArticles(): void
    {
        $articles = [new Article()];
        $this->articleRepository->expects($this->once())->method('findBy')->with(['status' => 'published'], ['publish_time' => 'DESC'])->willReturn($articles);

        // call tested method
        $result = $this->articleManager->getPublicArticles();

        // assert result
        $this->assertCount(1, $result);
    }

    /**
     * Test getArticle
     *
     * @return void
     */
    public function testGetArticle(): void
    {
        $article = new Article();
        $this->articleRepository->expects($this->once())->method('find')->with(1)->willReturn($article);

        // call tested method
        $result = $this->articleManager->getArticle(1);

        // assert result
        $this->assertSame($article, $result);
    }

    /**
     * Test createArticle success
     *
     * @return void
     */
    public function testCreateArticleSuccess(): void
    {
        // setup slugger mock
        $this->slugger->method('slug')->willReturn(new UnicodeString('Title'));

        // setup sanitizer mock
        $this->htmlSanitizer->method('sanitize')->with('Content')->willReturn('Sanitized Content');

        // setup repository mock for unique slug check
        $this->articleRepository->expects($this->once())->method('findOneBy')->with(['slug' => 'title'])->willReturn(null);

        // expect entity manager calls
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->articleManager->createArticle('Title', 'Content', 'published');
    }

    /**
     * Test createArticle failure
     *
     * @return void
     */
    public function testCreateArticleFailure(): void
    {
        // setup slugger mock
        $this->slugger->method('slug')->willReturn(new UnicodeString('Title'));

        // setup sanitizer mock
        $this->htmlSanitizer->method('sanitize')->willReturn('Sanitized Content');

        // setup repository mock
        $this->articleRepository->method('findOneBy')->willReturn(null);

        // simulate database error
        $this->entityManager->method('flush')->willThrowException(new Exception('DB Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('Error creating article'),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->articleManager->createArticle('Title', 'Content', 'published');
    }

    /**
     * Test updateArticle success
     *
     * @return void
     */
    public function testUpdateArticleSuccess(): void
    {
        $article = new Article();

        // setup slugger mock
        $this->slugger->method('slug')->willReturn(new UnicodeString('New Title'));

        // setup sanitizer mock
        $this->htmlSanitizer->method('sanitize')->with('New Content')->willReturn('Sanitized Content');

        // setup repository mock for unique slug check
        $this->articleRepository->expects($this->once())->method('findOneBy')->with(['slug' => 'new title'])->willReturn(null);

        // expect entity flush
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->articleManager->updateArticle($article, 'New Title', 'New Content', 'draft');

        // assert result
        $this->assertEquals('New Title', $article->getTitle());
        $this->assertEquals('Sanitized Content', $article->getContent());
        $this->assertEquals('draft', $article->getStatus());
        $this->assertInstanceOf(DateTime::class, $article->getEditedTime());
    }

    /**
     * Test updateArticle failure
     *
     * @return void
     */
    public function testUpdateArticleFailure(): void
    {
        $article = new Article();

        // setup slugger mock
        $this->slugger->method('slug')->willReturn(new UnicodeString('Title'));

        // setup sanitizer mock
        $this->htmlSanitizer->method('sanitize')->willReturn('Sanitized Content');

        // setup repository mock
        $this->articleRepository->method('findOneBy')->willReturn(null);

        // simulate database error
        $this->entityManager->method('flush')->willThrowException(new Exception('Update Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('Error updating article'),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->articleManager->updateArticle($article, 'Title', 'Content', 'published');
    }

    /**
     * Test deleteArticle success
     *
     * @return void
     */
    public function testDeleteArticleSuccess(): void
    {
        $article = new Article();

        // expect remove and flush
        $this->entityManager->expects($this->once())->method('remove')->with($article);
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->articleManager->deleteArticle($article);
    }

    /**
     * Test deleteArticle failure
     *
     * @return void
     */
    public function testDeleteArticleFailure(): void
    {
        $article = new Article();

        // simulate database error
        $this->entityManager->method('flush')->willThrowException(new Exception('Delete Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('Error deleting article'),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->articleManager->deleteArticle($article);
    }
}
