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
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findAllTransactions($type, $courseCode, $skipExpired)
    {
        $serializer = SerializerBuilder::create()->build();
        
        $transactions = $this->createQueryBuilder('t')
        ->select('t.id', 't.expireAt', 't.expireAt as created_at', 't.type', 't.course', 't.value')
        ->getQuery()
        ->getResult();

        if (!$transactions) {
            return json_encode(['message' => 'No transactions found']);
        } else {
            for ($i = 0; $i < count($transactions); $i++) {
                if ($transactions[$i]['type'] == 0) {
                    $transactions[$i]['type'] = "payment";
                } elseif ($transactions[$i]['type'] == 1) {
                    $transactions[$i]['type'] = 'deposit';
                    unset($transactions[$i]['course']);
                }
                $transactions[$i]['created_at'] = (($transactions[$i]['created_at'])->modify('-1 month'));
            }

            $filteredTransactions = array_values($this->filterTransactions($transactions, $type, $courseCode, $skipExpired));

            for ($i = 0; $i < count($filteredTransactions); $i++) {
                unset($filteredTransactions[$i]['expireAt']);
            }
            
            return $serializer->serialize($filteredTransactions, 'json');
        }
    }

    public function filterTransactions($transactions, $type, $courseCode, $skipExpired)
    {
        if ($type == 'payment' && $skipExpired == true && !empty($courseCode)) {
            return array_filter($transactions, function ($item) use ($courseCode) {
                if ($item['type'] == 'payment' && $item['course'] == $courseCode && ($item['expireAt']) < (new \DateTime())) {
                    return true;
                } else {
                    return false;
                }
            });
        } elseif ($type == 'payment' && !empty($courseCode)) {
            return array_filter($transactions, function ($item) use ($courseCode) {
                if ($item['type'] == 'payment' && $item['course'] == $courseCode) {
                    return true;
                } else {
                    return false;
                }
            });
        } elseif ($skipExpired == true && !empty($courseCode)) {
            return array_filter($transactions, function ($item) use ($courseCode) {
                if ($item['course'] == $courseCode && ($item['expireAt']) < (new \DateTime())) {
                    return true;
                } else {
                    return false;
                }
            });
        } elseif ($type == 'payment' && $skipExpired == true) {
            return array_filter($transactions, function ($item) use ($courseCode) {
                if ($item['type'] == 'payment' && ($item['expireAt']) < (new \DateTime())) {
                    return true;
                } else {
                    return false;
                }
            });
        } elseif ($type == 'deposit' && $skipExpired == true) {
            return array_filter($transactions, function ($item) use ($courseCode) {
                if ($item['type'] == 'deposit' && ($item['expireAt']) < (new \DateTime())) {
                    return true;
                } else {
                    return false;
                }
            });
        } elseif (!empty($courseCode)) {
            return array_filter($transactions, function ($item) use ($courseCode) {
                if (array_key_exists('course', $item)) {
                    if ($item['course'] == $courseCode) {
                        return true;
                    } else {
                        return false;
                    }
                }
            });
        } elseif (!empty($skipExpired) && $skipExpired == true) {
            return array_filter($transactions, function ($item) {
                if ((new $item['expireAt']) < (new \DateTime())) {
                    return true;
                } else {
                    return false;
                }
            });
        } elseif ($type == 'deposit') {
            return array_filter($transactions, function ($item) {
                if ($item['type'] == 'deposit') {
                    return true;
                } else {
                    return false;
                }
            });
        } elseif ($type == 'payment') {
            return array_filter($transactions, function ($item) {
                if ($item['type'] == 'payment') {
                    return true;
                } else {
                    return false;
                }
            });
        } else {
            return $transactions;
        }
    }

    public function addTransaction($userId, $courseCode, $amount, $type)
    {
        $entityManager = $this->getEntityManager();

        $transaction = new Transaction();
        $transaction->setUserId($userId);
        $transaction->setCourse($courseCode);
        $transaction->setType($type);
        $transaction->setValue($amount);
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
            $expireTime = $this->addTransaction($userId, $courseCode, $coursePrice, 0);
            
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
            $this->addTransaction($userId, '', $amount, 1);
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

            if ($courseType== 0) {
                return "rent";
            } elseif ($courseType== 1) {
                return 'buy';
            } elseif ($courseType == 2) {
                return 'free';
            }

            return $courseType;
        }
    }
}
