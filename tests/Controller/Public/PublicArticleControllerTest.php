<?php

namespace App\Tests\Controller\Public;

use App\Tests\CustomTestCase;
use App\Controller\Public\ArticleController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class PublicArticleControllerTest
 *
 * Test cases for public article controller
 *
 * @package App\Tests\Public
 */
#[CoversClass(ArticleController::class)]
class PublicArticleControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test load public articles list
     *
     * @return void
     */
    public function testLoadPublicArticlesList(): void
    {
        $this->client->request('GET', '/articles');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('h1', 'Articles');
        $this->assertSelectorExists('div[class="article-feed"]');
    }

    /**
     * Test load public article detail
     *
     * @return void
     */
    public function testLoadPublicArticleDetail(): void
    {
        $this->client->request('GET', '/article/detail?slug=the-future-of-web-development-in-2026');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('a[href="/articles"]');
        $this->assertSelectorExists('div[class="article-content"]');
    }

    /**
     * Test load public article detail with nonexistent article
     *
     * @return void
     */
    public function testLoadPublicArticleDetailWithNonexistentArticle(): void
    {
        $this->client->request('GET', '/article/detail?slug=nonexistent-article');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
