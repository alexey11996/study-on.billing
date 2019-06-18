<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Course;
use App\Entity\Transaction;

class CourseFixtures extends Fixture
{
    const PAYMENT_TYPE = 0;
    const DEPOSIT_TYPE = 1;

    const RENT_COURSE = 0;
    const BUY_COURSE = 1;
    const FREE_COURSE = 2;

    public function load(ObjectManager $manager)
    {
        $courseCode = ['mern-stack-front-to-back-full-stack-react-redux-node-js', 'build-a-blockchain-and-a-cryptocurrency-from-scratch', 'java-programming-masterclass-for-software-developers'];
        $courseType = [self::RENT_COURSE, self::BUY_COURSE, self::FREE_COURSE];
        $coursePrice = [25.55, 20.25, 0.0];

        $transactionSender = [9, 11, 14];
        $transactionForCourse = ['mern-stack-front-to-back-full-stack-react-redux-node-js', 'build-a-blockchain-and-a-cryptocurrency-from-scratch', 'java-programming-masterclass-for-software-developers'];
        $transactionType = [self::PAYMENT_TYPE, self::PAYMENT_TYPE, self::DEPOSIT_TYPE];
        $transactionValue = [250.2, 300.55, 275.45];
        $transactionExpireAt = [(new \DateTime())->modify('+1 month'), (new \DateTime())->modify('+1 day'), (new \DateTime())->modify('+1 second')];

        for ($i = 0; $i < 3; $i++) {
            $course = new Course();
            $course->setCode($courseCode[$i]);
            $course->setType($courseType[$i]);
            $course->setPrice($coursePrice[$i]);
            $manager->persist($course);
        }

        $manager->flush();

        $courses = $manager->getRepository(Course::class)->findAll();

        for ($i = 0; $i < 3; $i++) {
            $course = $manager->getRepository(Course::class)->find($courses[$i]->getId());
            $transaction = new Transaction();
            $transaction->setCreatedAt((new \DateTime()));
            $transaction->setUserId($transactionSender[$i]);
            $transaction->setCourse($course);
            $transaction->setType($transactionType[$i]);
            $transaction->setValue($transactionValue[$i]);
            $transaction->setExpireAt($transactionExpireAt[$i]);
            $manager->persist($transaction);
        }

        $manager->flush();
    }
}
