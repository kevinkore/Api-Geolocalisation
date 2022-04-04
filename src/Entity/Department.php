<?php

namespace App\Entity;

use App\Entity\Common;
use Doctrine\ORM\Mapping as ORM;
use App\Behviour\TimeBehviourTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
class Department
{
    use TimeBehviourTrait;

    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    private ?Uuid $id;

    #[ORM\Column(type:'string', length:'255')]
    private string $name;

    #[ORM\ManyToOne(inversedBy: 'department')]
    #[ORM\JoinColumn(nullable: false)]
    private Region $region;

    #[ORM\OneToMany(targetEntity: Common::class, mappedBy: "department", orphanRemoval: true)]
    private $common;


    public function __construct()
    {
        $this->commons = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): self
    {
        $this->region = $region;

        return $this;
    }

        /**
    * @return Collection|Common[]
    */
    public function getCommon(): Collection
    {
        return $this->common;
    }

    public function addCommon(Common $common): self
    {
        if (!$this->commons->contains($common)) {
            $this->commons[] = $common;
           $common->setDepartment($this);
        }

        return $this;
    }

    public function removeCommon(Common $common): self
    {
        if ($this->commons->contains($common)) {
            $this->commons->removeElement($common);
            // set the owning side to null (unless already changed)
            if ($common->getDepartment() === $this) {
                $common->setDepartment(null);
            }
        }

        return $this;
    }
}