<?php

namespace App\Behviour;


use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


trait TimeBehviourTrait
{

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(["details"])]
    protected  ?DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(["details"])]
    protected  ?DateTimeImmutable $updatedAt;

    /**
     * @return DateTimeImmutable|null
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreFlush]
    public function setUpdatedAt(): self
    {
        $this->updatedAt = new  \DateTimeImmutable();

        return $this;
    }

}
