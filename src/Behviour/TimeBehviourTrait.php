<?php

namespace App\Behviour;


use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;


trait TimeBehviourTrait
{


    #[ORM\Column(type: 'datetime_immutable')]
    
    protected  ?DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]

    protected  ?DateTimeImmutable $updatedAt;

    /**
     * @return DateTimeImmutable|null
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new  DateTimeImmutable();

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
    /**
     * @ORM\PreFlush
     */
    public function setUpdatedAt(): self
    {
        $this->updatedAt = new  DateTimeImmutable();

        return $this;
    }

}
