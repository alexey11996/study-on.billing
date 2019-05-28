<?php

namespace App\Service;

use App\Entity\Transaction;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentService
{
    private $entityManager;

    private $initPayment;

    public function __construct($entityManager, $initPayment)
    {
        $this->entityManager = $entityManager;
        $this->initPayment = $initPayment;
    }

    public function depositTransaction($userId)
    {
        try {
            $this->entityManager->getRepository(Transaction::class)->addDepositTransaction($userId, $this->initPayment);
            return "Success";
        } catch (Exception $e) {
            return json_encode(['code' => $e->getStatusCode(), 'message' => $e->getMessage()]);
        }
    }

    public function paymentTransaction($userId, $courseCode)
    {
        try {
            return $this->entityManager->getRepository(Transaction::class)->addPaymentTransaction($userId, $courseCode);
        } catch (HttpException $e) {
            return json_encode(['code' => $e->getStatusCode(), 'message' => $e->getMessage()]);
        }
    }
}
