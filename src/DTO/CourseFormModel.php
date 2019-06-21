<?php

namespace App\DTO;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

class CourseFormModel
{
    const COURSE_TYPES = ['rent', 'buy', 'free'];
    /**
     * @Assert\NotBlank(message="Blank code")
     * @JMS\Type("string")
     */
    public $code;

    /**
     * @Assert\NotBlank(message="Blank title")
     * @JMS\Type("string")
     */
    public $title;

    /**
     * @Assert\NotBlank(message="Blank type")
     * @Assert\Choice(choices=CourseFormModel::COURSE_TYPES, message="Choose a valid type")
     * @JMS\Type("string")
     */
    public $type;

    /**
     * @Assert\NotBlank(message="Blank title")
     * @JMS\Type("float")
     */
    public $price;
}
