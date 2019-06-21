<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use App\Service\Twig;

class EndRentNotification extends Command
{
    private $twig;
    private $mailer;
    private $sendFrom;

    protected static $defaultName = 'payment:ending:notification';

    public function __construct(Twig $twig, \Swift_Mailer $mailer, $sendFrom)
    {
        $this->sendFrom = $sendFrom;
        $this->twig = $twig;
        $this->mailer = $mailer;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $html = $this->twig->render(
            'endRent.html.twig'
        );
        $message = (new \Swift_Message('End Rent Notification'))
            ->setFrom($this->sendFrom)
            ->setTo('recipient@example.com')
            ->setBody($html, 'text/html');

        $this->mailer->send($message);
    }
}
