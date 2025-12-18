<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\LogRepository;

/**
 * Class Log
 *
 * The Log entity represents table in the database
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'logs')]
#[ORM\Index(name: 'logs_name_idx', columns: ['name'])]
#[ORM\Index(name: 'logs_status_idx', columns: ['status'])]
#[ORM\Index(name: 'logs_ip_address_idx', columns: ['ip_address'])]
#[ORM\Entity(repositoryClass: LogRepository::class)]
class Log
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $value = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $time = null;

    #[ORM\Column(length: 255)]
    private ?string $ip_address = null;

    #[ORM\Column(length: 255)]
    private ?string $browser = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    #[ORM\JoinColumn(name: "visitors", referencedColumnName: "id")]
    private ?int $visitor_id = null;

    /**
     * Get the log id
     *
     * @return int|null The log id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the log name
     *
     * @return string|null The log name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the log name
     *
     * @param string $name The log name
     *
     * @return static The log object
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the log value
     *
     * @return string|null The log value
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set the log value
     *
     * @param string $value The log value
     *
     * @return static The log object
     */
    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the log time
     *
     * @return DateTimeInterface|null The log time
     */
    public function getTime(): ?DateTimeInterface
    {
        return $this->time;
    }

    /**
     * Set the log time
     *
     * @param DateTimeInterface $time The log time
     *
     * @return static The log object
     */
    public function setTime(DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get the log ip address
     *
     * @return string|null The log ip address
     */
    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    /**
     * Set the log ip address
     *
     * @param string $ip_address The log ip address
     *
     * @return static The log object
     */
    public function setIpAddress(string $ip_address): static
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    /**
     * Get the log browser
     *
     * @return string|null The log browser
     */
    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    /**
     * Set the log browser
     *
     * @param string $browser The log browser
     *
     * @return static The log object
     */
    public function setBrowser(string $browser): static
    {
        $this->browser = $browser;

        return $this;
    }

    /**
     * Get the log status
     *
     * @return string|null The log status
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set the log status
     *
     * @param string $status The log status
     *
     * @return static The log object
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the visitor id
     *
     * @return int|null The visitor id
     */
    public function getVisitorId(): ?int
    {
        return $this->visitor_id;
    }

    /**
     * Set the visitor id
     *
     * @param int $visitor_id The visitor id
     *
     * @return static The log object
     */
    public function setVisitorId(int $visitor_id): static
    {
        $this->visitor_id = $visitor_id;

        return $this;
    }
}
