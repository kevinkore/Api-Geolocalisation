<?php

namespace App\Entity;

use App\Entity\Common;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use App\Behviour\TimeBehviourTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Department
{
    use TimeBehviourTrait;

    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    #[Groups(["details","summary"])]
    private ?Uuid $id;

    #[ORM\Column(type:'string', length:'255', unique:true )]
    #[Groups(["details","summary"])]
    private string $name;

    #[ORM\ManyToOne(inversedBy: 'departments', targetEntity: Region::class, fetch: 'EXTRA_LAZY', cascade: ["persist"])]
    #[ORM\JoinColumn(nullable: false, name: 'region_id', referencedColumnName: 'id')]
    #[Groups(["referenceVille"])]
    private Region $region;

    #[ORM\OneToMany(targetEntity: Common::class, mappedBy: "department", orphanRemoval: true )]
    private $commons;

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