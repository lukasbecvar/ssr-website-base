<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\MessageRepository;

/**
 * Class Message
 *
 * The Message entity represents table in the database
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'inbox_messages')]
#[ORM\Index(name: 'inbox_messages_name_idx', columns: ['name'])]
#[ORM\Index(name: 'inbox_messages_email_idx', columns: ['email'])]
#[ORM\Index(name: 'inbox_messages_status_idx', columns: ['status'])]
#[ORM\Index(name: 'inbox_messages_ip_address_idx', columns: ['ip_address'])]
#[ORM\Index(name: 'inbox_messages_visitor_id_idx', columns: ['visitor_id'])]
#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $time = null;

    #[ORM\Column(length: 255)]
    private ?string $ip_address = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    #[ORM\JoinColumn(name: "visitors", referencedColumnName: "id")]
    private ?int $visitor_id = null;

    /**
     * Get message id
     *
     * @return int|null The message id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get message name
     *
     * @return string|null The message name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set message name
     *
     * @param string $name The message name
     *
     * @return static The message object
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get message email
     *
     * @return string|null The message email
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set message email
     *
     * @param string $email The message email
     *
     * @return static The message object
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get message message
     *
     * @return string|null The message message
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set message message
     *
     * @param string $message The message message
     *
     * @return static The message object
     */
    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message time
     *
     * @return DateTimeInterface|null The message time
     */
    public function getTime(): ?DateTimeInterface
    {
        return $this->time;
    }

    /**
     * Set message time
     *
     * @param DateTimeInterface $time The message time
     *
     * @return static The message object
     */
    public function setTime(DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get message ip address
     *
     * @return string|null The message ip address
     */
    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    /**
     * Set message ip address
     *
     * @param string $ip_address The message ip address
     *
     * @return static The message object
     */
    public function setIpAddress(string $ip_address): static
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    /**
     * Get message status
     *
     * @return string|null The message status
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set message status
     *
     * @param string $status The message status
     *
     * @return static The message object
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get visitor id
     *
     * @return int|null The visitor id
     */
    public function getVisitorID(): ?int
    {
        return $this->visitor_id;
    }

    /**
     * Set visitor id
     *
     * @param int $visitor_id The visitor id
     *
     * @return static The message object
     */
    public function setVisitorID(int $visitor_id): static
    {
        $this->visitor_id = $visitor_id;

        return $this;
    }
}
