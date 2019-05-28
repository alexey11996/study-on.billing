<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Course;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        /**
         * Course types: 0 - rent, 1 - buy, 2 - free
         */
        $courseCode = ['mern-stack-front-to-back-full-stack-react-redux-node-js', 'build-a-blockchain-and-a-cryptocurrency-from-scratch', 'java-programming-masterclass-for-software-developers'];
        $courseType = [0, 1, 2];
        $coursePrice = [25.55, 20.25, 0.0];

        for ($i = 0; $i < 3; $i++) {
            $course = new Course();
            $course->setCode($courseCode[$i]);
            $course->setType($courseType[$i]);
            $course->setPrice($coursePrice[$i]);
            $manager->persist($course);
        }

        $manager->flush();
    }
}
