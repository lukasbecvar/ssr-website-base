<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ArticleRepository;

/**
 * Class Article
 *
 * The Article entity represents table in the database
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'articles')]
#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'draft';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $publish_time = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $edited_time = null;

    /**
     * Get the article id
     *
     * @return int|null The article id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the article title
     *
     * @return string|null The article title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set the article title
     *
     * @param string $title The article title
     *
     * @return static The article object
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the article slug
     *
     * @return string|null The article slug
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * Set the article slug
     *
     * @param string $slug The article slug
     *
     * @return static The article object
     */
    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get the article content
     *
     * @return string|null The article content
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Set the article content
     *
     * @param string $content The article content
     *
     * @return static The article object
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get the article status
     *
     * @return string|null The article status
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set the article status
     *
     * @param string $status The article status
     *
     * @return static The article object
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the article publish time
     *
     * @return DateTimeInterface|null The article publish time
     */
    public function getPublishTime(): ?DateTimeInterface
    {
        return $this->publish_time;
    }

    /**
     * Set the article publish time
     *
     * @param DateTimeInterface $publish_time The article publish time
     *
     * @return static The article object
     */
    public function setPublishTime(DateTimeInterface $publish_time): static
    {
        $this->publish_time = $publish_time;

        return $this;
    }

    /**
     * Get the article edited time
     *
     * @return DateTimeInterface|null The article edited time
     */
    public function getEditedTime(): ?DateTimeInterface
    {
        return $this->edited_time;
    }

    /**
     * Set the article edited time
     *
     * @param DateTimeInterface|null $edited_time The article edited time
     *
     * @return static The article object
     */
    public function setEditedTime(?DateTimeInterface $edited_time): static
    {
        $this->edited_time = $edited_time;

        return $this;
    }
}
