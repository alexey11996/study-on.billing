<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\BillingUser;
use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\Serializer\SerializerBuilder;

define('PAYMENT', 0);
define('DEPOSIT', 1);

define('RENT', 0);
define('BUY', 1);
define('FREE', 2);

/**
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findAllTransactions($parameters, $skipExpired)
    {
        $finalTransactions = [];

        $serializer = SerializerBuilder::create()->build();
        
        $transactions = $this->findBy($parameters);

        foreach ($transactions as $transaction) {
            $tempArray = [];
            if (isset($skipExpired) && $skipExpired == true) {
                if (($transaction->getExpireAt()) > (new \DateTime())) {
                    $tempArray['id'] = $transaction->getId();
                    $tempArray['created_at'] = $transaction->getCreatedAt();
                    switch ($transaction->getType()) {
                        case PAYMENT:
                            $tempArray['type'] = "payment";
                            break;
                        case DEPOSIT:
                            $tempArray['type'] = 'deposit';
                            break;
                    }
                    if ($transaction->getCourse() != null) {
                        $tempArray['course_code'] = ($transaction->getCourse())->getCode();
                    }
                    $tempArray['amount'] = $transaction->getValue();
                    array_push($finalTransactions, $tempArray);
                }
            } else {
                $tempArray['id'] = $transaction->getId();
                $tempArray['created_at'] = $transaction->getCreatedAt();
                switch ($transaction->getType()) {
                    case PAYMENT:
                        $tempArray['type'] = "payment";
                        break;
                    case DEPOSIT:
                        $tempArray['type'] = 'deposit';
                        break;
                }
                if ($transaction->getCourse() != null) {
                    $tempArray['course_code'] = ($transaction->getCourse())->getCode();
                }
                $tempArray['amount'] = $transaction->getValue();
                array_push($finalTransactions, $tempArray);
            }
        }
 
        return $serializer->serialize($finalTransactions, 'json');
    }

    public function addTransaction($userId, $courseCode, $amount, $type)
    {
        $entityManager = $this->getEntityManager();

        $transaction = new Transaction();
        $transaction->setUserId($userId);

        $course = $entityManager->getRepository(Course::class)->findOneBy(['code' => $courseCode]);

        $transaction->setCourse($course);
        $transaction->setType($type);
        $transaction->setValue($amount);
        $transaction->setCreatedAt((new \DateTime()));
        $expireTime = (new \DateTime())->modify('+1 month');
        $transaction->setExpireAt($expireTime);

        $entityManager->persist($transaction);
        $entityManager->flush();

        return $expireTime->format("Y-m-d\TH:i:sP");
    }

    public function addPaymentTransaction($userId, $courseCode)
    {
        $entityManager = $this->getEntityManager();

        $entityManager->getConnection()->beginTransaction();
        try {
            $courseType = $this->decreaseBalance($userId, $courseCode);

            $coursePrice = $entityManager->getRepository(Course::class)->findOneBy(['code' => $courseCode])->getPrice();
            $expireTime = $this->addTransaction($userId, $courseCode, $coursePrice, PAYMENT);
            
            $entityManager->getConnection()->commit();

            return json_encode(['success' => true, 'course_type' => $courseType, 'exrires_at' => $expireTime]);
        } catch (HttpException $e) {
            $entityManager->getConnection()->rollBack();
            throw $e;
        }
    }

    public function addDepositTransaction($userId, $amount)
    {
        $entityManager = $this->getEntityManager();

        $entityManager->getConnection()->beginTransaction();
        try {
            $this->addTransaction($userId, '', $amount, DEPOSIT);
            $this->increaseBalance($userId, $amount);
            $entityManager->getConnection()->commit();
        } catch (HttpException $e) {
            $entityManager->getConnection()->rollBack();
            throw $e;
        }
    }

    public function increaseBalance($userId, $amount)
    {
        $entityManager = $this->getEntityManager();

        $user = $entityManager->getRepository(BillingUser::class)->findOneBy(['id' => $userId]);

        $currentBalance = $user->getBalance();

        $newBalance = $currentBalance + $amount;

        $user->setBalance($newBalance);
        
        $entityManager->persist($user);
        $entityManager->flush();
    }

    public function decreaseBalance($userId, $courseCode)
    {
        $entityManager = $this->getEntityManager();

        $user = $entityManager->getRepository(BillingUser::class)->findOneBy(['id' => $userId]);

        $currentBalance = $user->getBalance();
        
        $course = $entityManager->getRepository(Course::class)->findOneBy(['code' => $courseCode]);

        $coursePrice = $course->getPrice();

        if ($currentBalance < $coursePrice) {
            throw new HttpException(400, "Not enough cash in your account");
        } else {
            $newBalance = $currentBalance - $coursePrice;

            $user->setBalance($newBalance);
            
            $entityManager->persist($user);
            $entityManager->flush();

            $courseType = $course->getType();

            switch ($courseType) {
                case 0:
                    return 'rent';
                    break;
                case 1:
                    return 'buy';
                    break;
                case 2:
                    return 'free';
                    break;
            }
        }
    }
}
