<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CourseRepository")
 */
class Course
{
    const RENT_COURSE = 0;
    const BUY_COURSE = 1;
    const FREE_COURSE = 2;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="smallint")
     */
    private $type;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Transaction", mappedBy="course")
     */
    private $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getConvertedType(): ?string
    {
        switch ($this->getType()) {
            case self::RENT_COURSE:
                return 'rent';
                break;
            case  self::BUY_COURSE:
                return 'buy';
                break;
            case self::FREE_COURSE:
                return 'free';
                break;
        }
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setCourse($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->contains($transaction)) {
            $this->transactions->removeElement($transaction);
            // set the owning side to null (unless already changed)
            if ($transaction->getCourse() === $this) {
                $transaction->setCourse(null);
            }
        }

        return $this;
    }

    public static function fromDto($course, $userDto)
    {
        if (isset($course)) {
            $course->setCode($userDto->code);
            $course->setTitle($userDto->title);
            switch ($userDto->type) {
                case 'rent':
                    $userDto->type = 0;
                    break;
                case 'buy':
                    $userDto->type = 1;
                    break;
                case 'free':
                    $userDto->type = 2;
                    break;
            }
            $course->setType($userDto->type);
            $course->setPrice($userDto->price);
        } else {
            $course = new Course();
            $course->setCode($userDto->code);
            $course->setTitle($userDto->title);
            switch ($userDto->type) {
                case 'rent':
                    $userDto->type = 0;
                    break;
                case 'buy':
                    $userDto->type = 1;
                    break;
                case 'free':
                    $userDto->type = 2;
                    break;
            }
            $course->setType($userDto->type);
            $course->setPrice($userDto->price);
        }
        return $course;
    }
}
