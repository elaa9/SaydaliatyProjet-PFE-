<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[UniqueEntity('registrationNumber')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $creationDate = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $registrationNumber = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    private ?float $price = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    private ?bool $statue = null;

    #[ORM\ManyToOne]
    private ?Customer $Customer = null;

    #[ORM\ManyToOne]
    private ?Pharmacist $Pharmacist = null;

    #[ORM\ManyToOne]
    private ?Delivery $Delivery = null;

    #[ORM\ManyToOne]
    private ?Product $Product = null;

    #[ORM\ManyToOne]
    private ?Prescription $Prescription = null;


    public function __construct()
    {
        $this->creationDate = new \DateTimeImmutable;

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreationDate(): ?\DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeImmutable $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(string $registrationNumber): static
    {
        $this->registrationNumber = $registrationNumber;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function isStatue(): ?bool
    {
        return $this->statue;
    }

    public function setStatue(bool $statue): static
    {
        $this->statue = $statue;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->Customer;
    }

    public function setCustomer(?Customer $Customer): static
    {
        $this->Customer = $Customer;

        return $this;
    }

    public function getPharmacist(): ?Pharmacist
    {
        return $this->Pharmacist;
    }

    public function setPharmacist(?Pharmacist $Pharmacist): static
    {
        $this->Pharmacist = $Pharmacist;

        return $this;
    }

    public function getDelivery(): ?Delivery
    {
        return $this->Delivery;
    }

    public function setDelivery(?Delivery $Delivery): static
    {
        $this->Delivery = $Delivery;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->Product;
    }

    public function setProduct(?Product $Product): static
    {
        $this->Product = $Product;

        return $this;
    }

    public function getPrescription(): ?Prescription
    {
        return $this->Prescription;
    }

    public function setPrescription(?Prescription $Prescription): static
    {
        $this->Prescription = $Prescription;

        return $this;
    }

}
