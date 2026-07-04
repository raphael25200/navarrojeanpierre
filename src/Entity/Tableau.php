<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\TableauRepository;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;



#[ORM\Entity(repositoryClass: TableauRepository::class)]
#[UniqueEntity('slug')]
#[Vich\Uploadable()]
class Tableau
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\Length(min: 1)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(min: 5)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $keywords = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $dimension = null;

    #[ORM\Column(nullable: true)]
    private ?bool $forsale = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[Vich\UploadableField(mapping: 'tableaux', fileNameProperty: 'image')]
    #[Assert\Image()]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $preview = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $display = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    #[Assert\Length(min: 3)]
    #[Assert\Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Invalid Slug')]
    private ?string $slug = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'tableaux')]
    private ?Category $category = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_in_slider = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $orientation = null;

    #[ORM\Column(nullable: true)]
    private ?int $numero_tableau = null;


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaires = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $customTitle = null;

    /**
     * @var Collection<int, Avis>
     */
    #[ORM\OneToMany(mappedBy: 'tableau', targetEntity: Avis::class)]
    private Collection $avis;

    public function __construct()
    {
        $this->avis = new ArrayCollection();
    }

    public function getCommentaires(): ?string
    {
        return $this->commentaires;
    }

    // src/Entity/Tableau.php
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ariaLabel = null;

    public function getAriaLabel(): ?string
    {
        return $this->ariaLabel;
    }

    public function setAriaLabel(?string $ariaLabel): self
    {
        $this->ariaLabel = $ariaLabel;
        return $this;
    }

    public function setCommentaires(?string $commentaires): self
    {
        $this->commentaires = $commentaires;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile): static
    {
        $this->imageFile = $imageFile;

        if ($imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(string $keywords): static
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getDimension(): ?string
    {
        return $this->dimension;
    }

    public function setDimension(?string $dimension): static
    {
        $this->dimension = $dimension;

        return $this;
    }

    public function isForsale(): ?bool
    {
        return $this->forsale;
    }

    public function setForsale(bool $forsale): static
    {
        $this->forsale = $forsale;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): static
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getPreview(): ?string
    {
        return $this->preview;
    }

    public function setPreview(string $preview): static
    {
        $this->preview = $preview;

        return $this;
    }

    public function getDisplay(): ?string
    {
        return $this->display;
    }

    public function setDisplay(?string $display): static
    {
        $this->display = $display;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function isInSlider(): ?bool
    {
        return $this->is_in_slider;
    }

    public function setIsInSlider(bool $is_in_slider): static
    {
        $this->is_in_slider = $is_in_slider;

        return $this;
    }

    public function getOrientation(): ?string
    {
        return $this->orientation;
    }

    public function setOrientation(?string $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    public function getNumeroTableau(): ?int
    {
        return $this->numero_tableau;
    }

    public function setNumeroTableau(?int $numero_tableau): static
    {
        $this->numero_tableau = $numero_tableau;

        return $this;
    }



    public function getCustomTitle(): ?string
    {
        return $this->customTitle;
    }

    public function setCustomTitle(?string $customTitle): self
    {
        $this->customTitle = $customTitle;
        return $this;
    }

    /**
     * @return Collection<int, Avis>
     */
    public function getAvis(): Collection
    {
        return $this->avis;
    }

    public function addAvi(Avis $avi): static
    {
        if (!$this->avis->contains($avi)) {
            $this->avis->add($avi);
            $avi->setTableau($this);
        }

        return $this;
    }

    public function removeAvi(Avis $avi): static
    {
        if ($this->avis->removeElement($avi)) {
            // set the owning side to null (unless already changed)
            if ($avi->getTableau() === $this) {
                $avi->setTableau(null);
            }
        }

        return $this;
    }
}
