<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use App\Entity\Transaction;
use App\Entity\BillingUser;
use App\Service\Twig;

class EndRentNotification extends Command
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

            foreach ($transactions as $transaction) {
                $finalTransactions[$transaction['userId']][] = $transaction;
            }

            for ($i = 0; $i < count($finalTransactions); $i++) {
                $html = $this->twig->render(
                    'endRent.html.twig',
                    ['courses' => $finalTransactions[$uniqueIds[$i]]]
                );
                $message = (new \Swift_Message('Срок аренды курсов подходит к концу'))
                    ->setFrom($this->sendFrom)
                    ->setTo($emails[$i]['email'])
                    ->setBody($html, 'text/html');

                $this->mailer->send($message);
            }
        }
    }
}
