<?php

namespace App\Entity;

use App\Entity\CommunalSector;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use App\Behviour\TimeBehviourTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Annotations as OA;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Common
{
    use TimeBehviourTrait;

    /**
     * @OA\Property(description="The unique identifier of common.")
     */
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    #[Groups(["details","summary","listCommon","full"])]
    private ?Uuid $id;

    /**
     * @OA\Property(type="string",description="The unique name of common." )
     */
    #[ORM\Column(type:'string', length:'255', unique:true )]
    #[Groups(["details","summary","listCommon","full"])]
    private string $name;

    #[ORM\ManyToOne(inversedBy: 'commons', targetEntity: Department::class, fetch: 'EXTRA_LAZY' , cascade: ["persist"])]
    #[ORM\JoinColumn(name: 'department_id', referencedColumnName: 'id')]
    #[Groups(["referenceVille","listCommon"])]
    private Department $department;

    #[ORM\OneToMany(targetEntity: CommunalSector::class, mappedBy: "common", orphanRemoval: true)]
    private $communalSectors;


    public function __construct()
    {
        $this->communalSectors = new ArrayCollection();
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

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): self
    {
        $this->department = $department;

        return $this;
    }

    /**
    * @return Collection|CommunalSector[]
    */
    public function getCommunalSector(): Collection
    {
        return $this->communalSector;
    }

    public function addCommunalSector(CommunalSector $communalSector): self
    {
        if (!$this->communalSectors->contains($communalSector)) {
            $this->communalSectors[] = $communalSector;
           $communalSector->setCommon($this);
        }

        return $this;
    }

    public function removeCommunalSector(CommunalSector $communalSector): self
    {
        if ($this->communalSectors->contains($communalSector)) {
            $this->communalSectors->removeElement($communalSector);
            // set the owning side to null (unless already changed)
            if ($communalSector->getCommon() === $this) {
                $communalSector->setCommon(null);
            }
        }

        return $this;
    }
}