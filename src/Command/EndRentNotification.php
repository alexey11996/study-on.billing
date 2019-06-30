<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use App\Entity\Transaction;
use App\Entity\BillingUser;
use App\Service\Twig;

class EndRentNotification extends ContainerAwareCommand
{
    private $twig;
    private $mailer;
    private $sendFrom;
    private $entityManager;

    protected static $defaultName = 'payment:ending:notification';

    public function __construct(Twig $twig, \Swift_Mailer $mailer, $sendFrom, $entityManager)
    {
        $this->sendFrom = $sendFrom;
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->sendEmails();
    }

    private function sendEmails()
    {
        $transactions = $this->entityManager->getRepository(Transaction::class)->findEndRentTransactions();

        if ($transactions) {
            $finalTransactions = [];

            $uniqueIds = array_unique(array_column($transactions, 'userId'));

            $emails = $this->entityManager->getRepository(BillingUser::class)->convertIdToEmail($uniqueIds);

            for ($i = 0; $i < count($uniqueIds); $i++) {
                $combined = [];
                foreach ($transactions as $transaction) {
                    if ($transaction['userId'] == $uniqueIds[$i]) {
                        $tempArr['title'] = $transaction['title'];
                        $tempArr['expireAt'] = $transaction['expireAt'];
                        array_push($combined, $tempArr);
                    }
                }
                array_push($finalTransactions, $combined);
            }

            for ($i = 0; $i < count($finalTransactions); $i++) {
                $html = $this->twig->render(
                    'endRent.html.twig',
                    ['courses' => $finalTransactions[$i]]
                );
                $message = (new \Swift_Message('Срок аренды курсов подходит к концу'))
                    ->setFrom($this->sendFrom)
                    ->setTo($emails[$i])
                    ->setBody($html, 'text/html');

                $this->mailer->send($message);
            }
        }
    }
}
