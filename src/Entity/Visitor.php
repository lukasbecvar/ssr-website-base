<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\VisitorRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Visitor
 *
 * The Visitor entity represents table in the database
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'visitors')]
#[ORM\Index(name: 'visitors_email_idx', columns: ['email'])]
#[ORM\Index(name: 'visitors_ip_address_idx', columns: ['ip_address'])]
#[ORM\Index(name: 'visitors_banned_status_idx', columns: ['banned_status'])]
#[ORM\Entity(repositoryClass: VisitorRepository::class)]
class Visitor
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $first_visit = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $last_visit = null;

    #[ORM\Column(length: 255)]
    private ?string $first_visit_site = null;

    #[ORM\Column(length: 255)]
    private ?string $browser = null;

    #[ORM\Column(length: 255)]
    private ?string $os = null;

    #[ORM\Column(length: 255)]
    private ?string $referer = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    private ?string $country = null;

    #[ORM\Column(length: 255)]
    private ?string $ip_address = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $banned_status = null;

    #[ORM\Column(length: 255)]
    private ?string $ban_reason = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $banned_time = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    /**
     * @var Collection<int, User> The users collection
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'visitor')]
    private Collection $users;

    /**
     * @var Collection<int, Message> The messages collection
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'visitor')]
    private Collection $messages;

    /**
     * @var Collection<int, Log> The logs collection
     */
    #[ORM\OneToMany(targetEntity: Log::class, mappedBy: 'visitor')]
    private Collection $logs;

    public function __construct()
    {
        $this->logs = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    /**
     * Get the visitor ID
     *
     * @return int|null The visitor ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the visitor ID safely (returns 0 if null)
     *
     * @return int The visitor ID (0 if null)
     */
    public function getIdSafe(): int
    {
        return $this->id ?? 0;
    }

    /**
     * Set the visitor ID
     *
     * @param int $id The visitor ID
     *
     * @return static The visitor object
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get visitor first visit
     *
     * @return DateTimeInterface|null The first visit
     */
    public function getFirstVisit(): ?DateTimeInterface
    {
        return $this->first_visit;
    }

    /**
     * Set visitor first visit
     *
     * @param DateTimeInterface $first_visit The first visit
     *
     * @return static The visitor object
     */
    public function setFirstVisit(DateTimeInterface $first_visit): static
    {
        $this->first_visit = $first_visit;

        return $this;
    }

    /**
     * Get visitor last visit
     *
     * @return DateTimeInterface|null The last visit
     */
    public function getLastVisit(): ?DateTimeInterface
    {
        return $this->last_visit;
    }

    /**
     * Set visitor last visit
     *
     * @param DateTimeInterface $last_visit The last visit
     *
     * @return static The visitor object
     */
    public function setLastVisit(DateTimeInterface $last_visit): static
    {
        $this->last_visit = $last_visit;

        return $this;
    }

    /**
     * Get visitor first visit site
     *
     * @return string|null The first visit site
     */
    public function getFirstVisitSite(): ?string
    {
        return $this->first_visit_site;
    }

    /**
     * Set visitor first visit site
     *
     * @param string|null $first_visit_site The first visit site
     *
     * @return static The visitor object
     */
    public function setFirstVisitSite(?string $first_visit_site): static
    {
        $this->first_visit_site = $first_visit_site;

        return $this;
    }

    /**
     * Get visitor browser
     *
     * @return string|null The browser
     */
    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    /**
     * Set visitor browser
     *
     * @param string $browser The browser
     *
     * @return static The visitor object
     */
    public function setBrowser(string $browser): static
    {
        $this->browser = $browser;

        return $this;
    }

    /**
     * Get visitor os
     *
     * @return string|null The os
     */
    public function getOs(): ?string
    {
        return $this->os;
    }

    /**
     * Set visitor os
     *
     * @param string $os The os
     *
     * @return static The visitor object
     */
    public function setOs(string $os): static
    {
        $this->os = $os;

        return $this;
    }

    /**
     * Get visitor referer
     *
     * @return string|null The referer
     */
    public function getReferer(): ?string
    {
        return $this->referer;
    }

    /**
     * Set visitor referer
     *
     * @param string $referer The referer
     *
     * @return static The visitor object
     */
    public function setReferer(string $referer): static
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * Get visitor city
     *
     * @return string|null The city name
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Set visitor city
     *
     * @param string $city The city name
     *
     * @return static The visitor object
     */
    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get visitor country
     *
     * @return string|null The country name
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * Set visitor country
     *
     * @param string $country The country name
     *
     * @return static The visitor object
     */
    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get visitor ip address
     *
     * @return string|null The ip address
     */
    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    /**
     * Set visitor ip address
     *
     * @param string $ip_address The ip address
     *
     * @return static The visitor object
     */
    public function setIpAddress(string $ip_address): static
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    /**
     * Get visitor banned status
     *
     * @return bool|null The banned status
     */
    public function getBannedStatus(): ?bool
    {
        return $this->banned_status;
    }

    /**
     * Set visitor banned status
     *
     * @param bool $banned_status The banned status
     *
     * @return static The visitor object
     */
    public function setBannedStatus(bool $banned_status): static
    {
        $this->banned_status = $banned_status;

        return $this;
    }

    /**
     * Get visitor ban reason
     *
     * @return string|null The ban reason
     */
    public function getBanReason(): ?string
    {
        return $this->ban_reason;
    }

    /**
     * Set visitor ban reason
     *
     * @param string $ban_reason The ban reason
     *
     * @return static The visitor object
     */
    public function setBanReason(string $ban_reason): static
    {
        $this->ban_reason = $ban_reason;

        return $this;
    }

    /**
     * Get visitor banned time
     *
     * @return DateTimeInterface|null The banned time
     */
    public function getBannedTime(): ?DateTimeInterface
    {
        return $this->banned_time;
    }

    /**
     * Set visitor banned time
     *
     * @param DateTimeInterface|null $banned_time The banned time
     *
     * @return static The visitor object
     */
    public function setBannedTime(?DateTimeInterface $banned_time): static
    {
        $this->banned_time = $banned_time;

        return $this;
    }

    /**
     * Get visitor email
     *
     * @return string|null The email
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set visitor email
     *
     * @param string $email The email
     *
     * @return static The visitor object
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get users
     *
     * @return Collection<int, User> The users collection
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * Add user
     *
     * @param User $user The user entity object
     *
     * @return static
     */
    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setVisitor($this);
        }

        return $this;
    }

    /**
     * Remove user
     *
     * @param User $user The user entity object
     *
     * @return static
     */
    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getVisitor() === $this) {
                $user->setVisitor(null);
            }
        }

        return $this;
    }

    /**
     * Get messages
     *
     * @return Collection<int, Message> The messages collection
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * Add message
     *
     * @param Message $message The message entity object
     *
     * @return static
     */
    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setVisitor($this);
        }

        return $this;
    }

    /**
     * Remove message
     *
     * @param Message $message The message entity object
     *
     * @return static
     */
    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getVisitor() === $this) {
                $message->setVisitor(null);
            }
        }

        return $this;
    }

    /**
     * Get logs
     *
     * @return Collection<int, Log> The logs collection
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    /**
     * Add log
     *
     * @param Log $log The log entity object
     *
     * @return static
     */
    public function addLog(Log $log): static
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setVisitor($this);
        }

        return $this;
    }

    /**
     * Remove log
     *
     * @param Log $log The log entity object
     *
     * @return static
     */
    public function removeLog(Log $log): static
    {
        if ($this->logs->removeElement($log)) {
            if ($log->getVisitor() === $this) {
                $log->setVisitor(null);
            }
        }

        return $this;
    }
}
