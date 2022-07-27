<?php 

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait TimesTampableTrait {
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function persistTimes() {
    	if($this->createdAt === null) {
    		$this->setCreatedAt(new \DateTimeImmutable);
    	}
    	$this->setUpdatedAt(new \DateTimeImmutable);
    }

}