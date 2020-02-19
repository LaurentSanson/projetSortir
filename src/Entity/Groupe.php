<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GroupeRepository")
 */
class Groupe
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Participant", inversedBy="groupes")
     */
    private $participants;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Participant", inversedBy="createurGroupes")
     */
    private $Createur;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Sortie", mappedBy="groupe")
     */
    private $sortie;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->sortie = new ArrayCollection();
    }

    public function  __toString()
    {
        return $this->nom;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Participant[]
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants[] = $participant;
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): self
    {
        if ($this->participants->contains($participant)) {
            $this->participants->removeElement($participant);
        }

        return $this;
    }

    public function getCreateur(): ?Participant
    {
        return $this->Createur;
    }

    public function setCreateur(?Participant $Createur): self
    {
        $this->Createur = $Createur;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * @return Collection|sortie[]
     */
    public function getSortie(): Collection
    {
        return $this->sortie;
    }

    public function addSortie(sortie $sortie): self
    {
        if (!$this->sortie->contains($sortie)) {
            $this->sortie[] = $sortie;
            $sortie->setGroupe($this);
        }

        return $this;
    }

    public function removeSortie(sortie $sortie): self
    {
        if ($this->sortie->contains($sortie)) {
            $this->sortie->removeElement($sortie);
            // set the owning side to null (unless already changed)
            if ($sortie->getGroupe() === $this) {
                $sortie->setGroupe(null);
            }
        }

        return $this;
    }
    
}
