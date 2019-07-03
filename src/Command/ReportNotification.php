<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use App\Entity\Transaction;
use App\Entity\BillingUser;
use App\Service\Twig;

class ReportNotification extends Command
{
    private $twig;
    private $mailer;
    private $sendFrom;
    private $sendTo;
    private $entityManager;

    protected static $defaultName = 'payment:report';

    public function __construct(Twig $twig, \Swift_Mailer $mailer, $sendFrom, $sendTo, $entityManager)
    {
        $this->sendFrom = $sendFrom;
        $this->sendTo = $sendTo;
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->entityManager->getRepository(Transaction::class)->generateMonthReport();
        if ($result) {
            $html = $this->twig->render(
                'monthReport.html.twig',
                ['courses' => $result['courses'], 'startDate' => $result['startDate'], 'endDate' => $result['endDate'], 'totalPrice' => $result['totalPrice']]
            );
            $message = (new \Swift_Message('Отчет об оплаченных курсах'))
            ->setFrom($this->sendFrom)
            ->setTo($this->sendTo)
            ->setBody($html, 'text/html');

            $this->mailer->send($message);
        }
    }
}
