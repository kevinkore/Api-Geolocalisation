<?php

namespace App\Entity;

use App\Entity\Common;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use App\Behviour\TimeBehviourTrait;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity()]
#[ORM\HasLifecycleCallbacks]
class CommunalSector
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

    #[ORM\ManyToOne(inversedBy: 'communalSector', targetEntity: Common::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(nullable: false, name: 'common_id', referencedColumnName: 'id')]
    private Common $common;


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

    public function getCommon(): ?Common
    {
        return $this->common;
    }

    public function setCommon(?Common $common): self
    {
        $this->common = $common;

        return $this;
    }
}