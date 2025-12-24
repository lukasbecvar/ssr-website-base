<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;

/**
 * Class User
 *
 * The User entity represents table in the database
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'users_role_idx', columns: ['role'])]
#[ORM\Index(name: 'users_token_idx', columns: ['token'])]
#[ORM\Index(name: 'users_name_idx', columns: ['username'])]
#[ORM\Index(name: 'users_ip_address_idx', columns: ['ip_address'])]
#[ORM\Index(name: 'users_visitor_id_idx', columns: ['visitor_id'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    #[ORM\Column(length: 255)]
    private ?string $ip_address = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $token = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $registed_time = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $last_login_time = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $profile_pic = null;

    #[ORM\ManyToOne(targetEntity: Visitor::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: "visitor_id", referencedColumnName: "id", nullable: true)]
    private ?Visitor $visitor = null;

    /**
     * Get visitor ID safely
     *
     * @return int The visitor ID (0 if null)
     */
    public function getVisitorIdSafe(): int
    {
        if ($this->visitor) {
            return $this->visitor->getIdSafe();
        }
        return 0;
    }

    /**
     * Get the user username
     *
     * @return string|null The user username
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Set the user username
     *
     * @param string $username The user username
     *
     * @return static The user object
     */
    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get the user password
     *
     * @return string|null The user password
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set the user password
     *
     * @param string $password The user password
     *
     * @return static The user object
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get the user role
     *
     * @return string|null The user role
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * Set the user role
     *
     * @param string $role The user role
     *
     * @return static The user object
     */
    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get the user ip address
     *
     * @return string|null The user ip address
     */
    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    /**
     * Set the user ip address
     *
     * @param string $ip_address The user ip address
     *
     * @return static The user object
     */
    public function setIpAddress(string $ip_address): static
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    /**
     * Get the user token
     *
     * @return string|null The user token
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Set the user token
     *
     * @param string $token The user token
     *
     * @return static The user object
     */
    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get the user registed time
     *
     * @return DateTimeInterface|null The user registed time
     */
    public function getRegistedTime(): ?DateTimeInterface
    {
        return $this->registed_time;
    }

    /**
     * Set the user registed time
     *
     * @param DateTimeInterface $registed_time The user registed time
     *
     * @return static The user object
     */
    public function setRegistedTime(DateTimeInterface $registed_time): static
    {
        $this->registed_time = $registed_time;

        return $this;
    }

    /**
     * Get the user last login time
     *
     * @return DateTimeInterface|null The user last login time
     */
    public function getLastLoginTime(): ?DateTimeInterface
    {
        return $this->last_login_time;
    }

    /**
     * Set the user last login time
     *
     * @param DateTimeInterface|null $last_login_time The user last login time
     *
     * @return static The user object
     */
    public function setLastLoginTime(?DateTimeInterface $last_login_time): static
    {
        $this->last_login_time = $last_login_time;

        return $this;
    }

    /**
     * Get the user profile picture (encoded in base64)
     *
     * @return string|null The user profile picture
     */
    public function getProfilePic(): ?string
    {
        return $this->profile_pic;
    }

    /**
     * Set the user profile picture (encoded in base64)
     *
     * @param string $profile_pic The user profile picture
     *
     * @return static The user object
     */
    public function setProfilePic(string $profile_pic): static
    {
        $this->profile_pic = $profile_pic;

        return $this;
    }

    /**
     * Get the user visitor
     *
     * @return Visitor|null The user visitor
     */
    public function getVisitor(): ?Visitor
    {
        return $this->visitor;
    }

    /**
     * Set the user visitor
     *
     * @param Visitor|null $visitor The user visitor
     *
     * @return static The user object
     */
    public function setVisitor(?Visitor $visitor): static
    {
        $this->visitor = $visitor;

        return $this;
    }
}
