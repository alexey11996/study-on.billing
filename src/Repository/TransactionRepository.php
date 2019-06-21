<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\BillingUser;
use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\Serializer\SerializerBuilder;

/**
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    const PAYMENT_TYPE = 0;
    const DEPOSIT_TYPE = 1;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findEndRentTransactions()
    {
        $serializer = SerializerBuilder::create()->build();

        $transactions = $this->createQueryBuilder('t')
            ->innerJoin("t.course", "c")
            ->andWhere("c.type = 0")
            ->andWhere(" t.expireAt BETWEEN :today AND :tommorrow")
            ->setParameter('today', date("Y-m-d", time()))
            ->setParameter('tommorrow', date("Y-m-d", strtotime('+24 hours')))
            ->getQuery()->execute();

        return $serializer->serialize($transactions, 'json');
    }

    public function findAllTransactions($user, $courseCode, $type, $skipExpired)
    {
        $serializer = SerializerBuilder::create()->build();

        $finalTransactions = [];

        $transactionsQB = $this->createQueryBuilder('t')->andWhere('t.userId = :user')->setParameter('user', $user->getId());

        if (isset($courseCode)) {
            $course = $this->getEntityManager()->getRepository(Course::class)->findOneBy(['code' => $courseCode]);
            if (!$course) {
                throw new HttpException(404, 'No course found');
            } else {
                $transactionsQB->andWhere('t.course = :course')->setParameter('course', $course);
            }
        } elseif (isset($type)) {
            if ($type == 'payment' || $type == 'deposit') {
                switch ($type) {
                    case 'payment':
                        $type = 0;
                        break;
                    case 'deposit':
                        $type = 1;
                        break;
            }
                $transactionsQB->andWhere('t.type = :type')->setParameter('type', $type);
            } else {
                throw new HttpException(400, 'Type must be payment or deposit');
            }
        } elseif (isset($skipExpired)) {
            $transactionsQB->andWhere("t.expireAt > :date")->setParameter('date', date("Y-m-d", time()));
        }

        $transactions = $transactionsQB->getQuery()->execute();

        foreach ($transactions as $transaction) {
            $tempArray = [];
            $tempArray['id'] = $transaction->getId();
            $tempArray['created_at'] = $transaction->getCreatedAt();
            $tempArray['type'] = $transaction->getConvertedType();

            if ($transaction->getCourse() != null) {
                $tempArray['course_code'] = ($transaction->getCourse())->getCode();
            }
            $tempArray['amount'] = $transaction->getValue();
            $tempArray['expires_at'] = $transaction->getExpireAt();
            array_push($finalTransactions, $tempArray);
        }
 
        return $serializer->serialize($finalTransactions, 'json');
    }

    public function addTransaction($userId, $course, $amount, $type)
    {
        $entityManager = $this->getEntityManager();

        $transaction = new Transaction();
        $transaction->setUserId($userId);
        $transaction->setCourse($course);
        $transaction->setType($type);
        $transaction->setValue($amount);
        $transaction->setCreatedAt((new \DateTime()));
        $expireTime = $_ENV['EXPIRE_TIME'];
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
            $course = $entityManager->getRepository(Course::class)->findOneBy(['code' => $courseCode]);

            if ($course) {
                $this->decreaseBalance($userId, $course);

                $expireTime = $this->addTransaction($userId, $course, $course->getPrice(), self::PAYMENT_TYPE);
                
                $entityManager->getConnection()->commit();
    
                return json_encode(['success' => true, 'course_type' => $course->getConvertedType(), 'exrires_at' => $expireTime]);
            } else {
                throw new HttpException(404, 'No course found');
            }
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
            $this->addTransaction($userId, null, $amount, self::DEPOSIT_TYPE);
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

    public function decreaseBalance($userId, $course)
    {
        $entityManager = $this->getEntityManager();

        $user = $entityManager->getRepository(BillingUser::class)->findOneBy(['id' => $userId]);

        $currentBalance = $user->getBalance();

        $coursePrice = $course->getPrice();

        if ($currentBalance < $coursePrice) {
            throw new HttpException(400, "Not enough cash in your account");
        } else {
            $newBalance = $currentBalance - $coursePrice;

            $user->setBalance($newBalance);
            
            $entityManager->persist($user);
            $entityManager->flush();
        }
    }
}
