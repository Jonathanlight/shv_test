<?php

namespace App\Entity;

use App\Entity\Interfaces\CommentInterface;
use App\Entity\Traits\CommentableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class HedgeComment implements CommentInterface
{
    use CommentableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Hedge")
     * @ORM\JoinColumn(nullable=false)
     */
    private $parent;
}
