<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PricerRepository")
 * @Vich\Uploadable
 * @ORM\HasLifecycleCallbacks()
 */
class Pricer
{
    use TimestampableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="pricers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $uploadUser;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $filename;

    /**
     * @Vich\UploadableField(mapping="pricer_files", fileNameProperty="file")
     * @var File
     */
    private $file;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $filePath;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return User|null
     */
    public function getUploadUser(): ?User
    {
        return $this->uploadUser;
    }

    /**
     * @param User|null $uploadUser
     * @return Pricer
     */
    public function setUploadUser(? User $uploadUser): self
    {
        $this->uploadUser = $uploadUser;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return Pricer
     */
    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * @param UploadedFile|null $file
     * @return Pricer
     */
    public function setFile(UploadedFile $file = null): Pricer
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     * @return Pricer
     */
    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }
}
