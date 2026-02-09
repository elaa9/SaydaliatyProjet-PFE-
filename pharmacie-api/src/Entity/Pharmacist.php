<?php

namespace App\Entity;

use App\Repository\PharmacistRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;



#[ORM\Entity(repositoryClass: PharmacistRepository::class)]
#[UniqueEntity('Email')]
class Pharmacist implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $FirstName = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $LastName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $Email = null;

    #[ORM\ManyToOne]
    #[Assert\NotBlank]
    private ?Pharmacy $Pharmacy = null;

    private ?string $plainPassword = null;

    /**
     * @var list<string> The pharmacists roles
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->FirstName;
    }

    public function setFirstName(string $FirstName): static
    {
        $this->FirstName = $FirstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->LastName;
    }

    public function setLastName(string $LastName): static
    {
        $this->LastName = $LastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->Email;
    }

    public function setEmail(string $Email): static
    {
        $this->Email = $Email;

        return $this;
    }

    public function getPharmacy(): ?Pharmacy
    {
        return $this->Pharmacy;
    }

    public function setPharmacy(Pharmacy $Pharmacy): static
    {
        $this->Pharmacy = $Pharmacy;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @see UserInterface
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every pharmacist at least has ROLE_PHARMACIST
        $roles[] = 'ROLE_PHARMACIST';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }
    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->Email;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static 
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }
}
