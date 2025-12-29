<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class ArticleRepositoryTest
 *
 * Test cases for ArticleRepository
 *
 * @package App\Tests\Repository
 */
class ArticleRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * Test finding articles
     *
     * @return void
     */
    public function testFindArticles(): void
    {
        // cleanup database
        $this->entityManager->createQuery('DELETE FROM App\Entity\Article')->execute();

        // create test article
        $article = new Article();
        $article->setTitle('Test Article');
        $article->setSlug('test-article');
        $article->setContent('Content');
        $article->setStatus('published');
        $article->setPublishTime(new DateTime());
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        // retrieve repository
        $repository = $this->entityManager->getRepository(Article::class);

        // test findBy
        $articles = $repository->findBy(['status' => 'published']);
        $this->assertCount(1, $articles);
        $this->assertEquals('Test Article', $articles[0]->getTitle());
    }
}
