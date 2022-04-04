<?php

namespace App\Entity;

use App\Entity\Common;
use Doctrine\ORM\Mapping as ORM;
use App\Behviour\TimeBehviourTrait;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
class CommunalSector
{

    use TimeBehviourTrait;

    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    private ?Uuid $id;

    #[ORM\Column(type:'string', length:'255')]
    private string $name;

    #[ORM\ManyToOne(inversedBy: 'communalSector')]
    #[ORM\JoinColumn(nullable: false)]
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