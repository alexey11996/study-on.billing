<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Transaction;

class TransactionFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        /**
         * Transaction types: 0 - payment, 1 - deposit
         */

        $transactionSender = [9, 11, 14];
        $transactionForCourse = ['mern-stack-front-to-back-full-stack-react-redux-node-js', 'build-a-blockchain-and-a-cryptocurrency-from-scratch', 'java-programming-masterclass-for-software-developers'];
        $transactionType = [0, 0, 1];
        $transactionValue = [250.2, 300.55, 275.45];
        $transactionExpireAt = [(new \DateTime())->modify('+1 month'), (new \DateTime())->modify('+1 day'), (new \DateTime())->modify('+1 hour')];

        for ($i = 0; $i < 3; $i++) {
            $transaction = new Transaction();
            $transaction->setUserId($transactionSender[$i]);
            $transaction->setCourse($transactionForCourse[$i]);
            $transaction->setType($transactionType[$i]);
            $transaction->setValue($transactionValue[$i]);
            $transaction->setExpireAt($transactionExpireAt[$i]);
            $manager->persist($transaction);
        }

        $manager->flush();
    }
}
