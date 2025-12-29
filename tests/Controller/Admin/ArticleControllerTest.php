<?php

namespace App\Tests\Controller\Admin;

use App\Tests\CustomTestCase;
use App\Controller\Admin\ArticleController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class ArticleControllerTest
 *
 * Test cases for admin article controller
 *
 * @package App\Tests\Admin
 */
#[CoversClass(ArticleController::class)]
class ArticleControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate login
        $this->simulateLogin($this->client);
    }

    /**
     * Test load articles list
     *
     * @return void
     */
    public function testLoadArticlesList(): void
    {
        $this->client->request('GET', '/admin/articles');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('div[class="database-table-scroll"]');
        $this->assertSelectorTextContains('.count-text-in-menu', 'Articles List');
    }

    /**
     * Test load new article form
     *
     * @return void
     */
    public function testLoadNewArticleForm(): void
    {
        $this->client->request('GET', '/admin/articles/new');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('.count-text-in-menu', 'New Article');
        $this->assertSelectorExists('form[name="article_form"]');
        $this->assertSelectorExists('input[name="article_form[title]"]');
        $this->assertSelectorExists('select[name="article_form[status]"]');
        $this->assertSelectorExists('textarea[name="article_form[content]"]');
    }

    /**
     * Test load edit article form with nonexistent article
     *
     * @return void
     */
    public function testLoadEditArticleFormWithNonexistentArticle(): void
    {
        $this->client->request('GET', '/admin/articles/edit?id=1001');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test submit new article form with empty title
     *
     * @return void
     */
    public function testSubmitNewArticleFormWithEmptyTitle(): void
    {
        $this->client->request('POST', '/admin/articles/new', [
            'article_form' => [
                'title' => '',
                'status' => 'published',
                'content' => '<p>Test article content</p>'
            ]
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertAnySelectorTextContains('li', 'Please enter a title');
    }

    /**
     * Test submit new article form
     *
     * @return void
     */
    public function testSubmitNewArticleForm(): void
    {
        $this->client->request('POST', '/admin/articles/new', [
            'article_form' => [
                'title' => 'Test Article',
                'status' => 'published',
                'content' => '<p>Test article content</p>'
            ]
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load edit article form
     *
     * @return void
     */
    public function testLoadEditArticleForm(): void
    {
        $this->client->request('GET', '/admin/articles/edit?id=1');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('.count-text-in-menu', 'Edit Article');
        $this->assertSelectorExists('form[name="article_form"]');
        $this->assertSelectorExists('input[name="article_form[title]"]');
        $this->assertSelectorExists('select[name="article_form[status]"]');
        $this->assertSelectorExists('textarea[name="article_form[content]"]');
    }

    /**
     * Test submit edit article form with empty title
     *
     * @return void
     */
    public function testSubmitEditArticleFormWithEmptyTitle(): void
    {
        $this->client->request('POST', '/admin/articles/edit?id=1', [
            'article_form' => [
                'title' => '',
                'status' => 'published',
                'content' => '<p>Test article content</p>'
            ]
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertAnySelectorTextContains('li', 'Please enter a title');
    }

    /**
     * Test submit edit article form
     *
     * @return void
     */
    public function testSubmitEditArticleForm(): void
    {
        $this->client->request('POST', '/admin/articles/edit?id=1', [
            'article_form' => [
                'title' => 'Test Article',
                'status' => 'published',
                'content' => '<p>Test article content</p>'
            ]
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test delete article
     *
     * @return void
     */
    public function testDeleteArticle(): void
    {
        $this->client->request('POST', '/admin/articles/delete?id=2', [
            'csrf_token' => $this->getCsrfToken($this->client)
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
