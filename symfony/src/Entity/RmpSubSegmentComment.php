<?php

namespace App\Entity;

use App\Entity\Interfaces\CommentInterface;
use App\Entity\Traits\CommentableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RmpSubSegmentCommentRepository")
 */
class RmpSubSegmentComment implements CommentInterface
{
    use CommentableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RmpSubSegment")
     * @ORM\JoinColumn(nullable=false)
     */
    private $parent;
}
