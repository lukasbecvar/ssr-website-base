<?php

namespace App\Tests\Entity;

use DateTime;
use App\Entity\Article;
use PHPUnit\Framework\TestCase;

/**
 * Class ArticleTest
 *
 * Test cases for Article entity
 *
 * @package App\Tests\Entity
 */
class ArticleTest extends TestCase
{
    /**
     * Test default values
     *
     * @return void
     */
    public function testDefaultValues(): void
    {
        $article = new Article();

        // assert default values
        $this->assertNull($article->getId());
        $this->assertNull($article->getTitle());
        $this->assertNull($article->getContent());
        $this->assertEquals('draft', $article->getStatus());
        $this->assertNull($article->getPublishTime());
        $this->assertNull($article->getEditedTime());
    }

    /**
     * Test getters and setters
     *
     * @return void
     */
    public function testGettersAndSetters(): void
    {
        $now = new DateTime();

        // set values
        $article = new Article();
        $article->setTitle('Test Title');
        $article->setContent('Test Content');
        $article->setStatus('published');
        $article->setPublishTime($now);
        $article->setEditedTime($now);

        // assert values
        $this->assertEquals('Test Title', $article->getTitle());
        $this->assertEquals('Test Content', $article->getContent());
        $this->assertEquals('published', $article->getStatus());
        $this->assertSame($now, $article->getPublishTime());
        $this->assertSame($now, $article->getEditedTime());
    }
}
