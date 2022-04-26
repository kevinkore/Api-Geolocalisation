<?php

namespace App\Entity;


use App\Entity\Department;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use App\Behviour\TimeBehviourTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Region
{
    use TimeBehviourTrait;

    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    #[Groups(["details","summary"])]
    private ?Uuid $id;

    #[ORM\Column(type:'string', length:'255' , unique:true)]
    #[Groups(["details","summary"])]
    private string $name;

    #[ORM\Column(type:'string', length:'255')]
    #[Groups(["details"])]
    private string $capital;


    #[ORM\ManyToOne(inversedBy: 'regions', targetEntity: District::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'district_id', referencedColumnName: 'id')]
    #[Groups(["details","referenceVille"])]
    private District $district;

    #[ORM\OneToMany(targetEntity: Department::class, mappedBy: "region", orphanRemoval: true)]
    private $departments;


    public function __construct()
    {
        $this->departments = new ArrayCollection();
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

    public function getCapital(): ?string
    {
        return $this->capital;
    }

    public function setCapital(string $capital): self
    {
        $this->capital = $capital;

        return $this;
    }

    public function getDistrict(): ?District
    {
        return $this->district;
    }

    public function setDistrict(?District $district): self
    {
        $this->district = $district;

        return $this;
    }

    /**
    * @return Collection|Department[]
    */
    public function getDepartement(): Collection
    {
        return $this->departments;
    }

    public function addDepartment(Department $department): self
    {
        if (!$this->departments->contains($department)) {
            $this->departments[] = $department;
           $department->setRegion($this);
        }

        return $this;
    }

    public function removeDepartment(Department $department): self
    {
        if ($this->departments->contains($department)) {
            $this->departments->removeElement($department);
            // set the owning side to null (unless already changed)
            if ($department->getRegion() === $this) {
                $department->setRegion(null);
            }
        }

        return $this;
    }
}