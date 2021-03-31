<?php

namespace SVB\Mailing\Command;

use Doctrine\DBAL\Driver\Exception;
use SVB\Mailing\Connector\ConnectorInterface;
use SVB\Mailing\Exception\MailingException;
use SVB\Mailing\Mail\MailInterface;
use SVB\Mailing\Mailer;
use SVB\Mailing\Repository\MailRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailWorkerCommand extends Command
{
    /** @var Mailer */
    private $mailer;

    /** @var MailRepository */
    private $mailRepository;

    public function setMailer(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function setMailRepository(MailRepository $mailRepository)
    {
        $this->mailRepository = $mailRepository;
    }

    protected function configure()
    {
        $this
            ->setName('svb:mailing:worker')
            ->setDescription('Used for cronjobs')
        ;
    }

    /**
     * @throws MailingException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = 0;
        foreach ($this->mailRepository->getUnhandledMails(50) as $mailRow) {
            if ($mailRow['tries'] > 5) {
                $this->mailRepository->markMailAsFailed($mailRow['id']);
                $output->writeln(sprintf(
                    'Mail #%s has been marked as failed because it took too many tries to send it to the user.',
                    $mailRow['id']
                ));
                $status = 1;
                continue;
            }

            $mail = $this->mailRepository->getMailFromDatabaseResult($mailRow);
            if (!$mail instanceof MailInterface) {
                $this->mailRepository->markMailAsFailed($mailRow['id']);
                $output->writeln(sprintf(
                    'Mail #%s has been marked as failed because the mailer was not able to deserialize a new MailInterface using the serialized data from the database.',
                    $mailRow['id']
                ));
                $status = 1;
                continue;
            }

            if (!empty($mailRow['api_identifier'])) {
                switch ($this->mailer->getMailStatus($mail, $mailRow['api_identifier'])) {
                    case ConnectorInterface::MAIL_STATUS_SUCCESS:
                        $this->mailRepository->markMailAsSucceeded($mailRow['id']);
                        $output->writeln(sprintf(
                            'Mail #%s has been marked as success.',
                            $mailRow['id']
                        ));
                        break;
                    case ConnectorInterface::MAIL_STATUS_FAILED:
                        $this->mailRepository->markMailAsFailed($mailRow['id']);
                        $output->writeln(sprintf(
                            'Mail #%s has been marked as failed because the Connector API responded with a failed mail status.',
                            $mailRow['id']
                        ));
                        $status = 1;
                        break;
                }
                continue;
            }

            try {
                $this->mailer->resendMail($mailRow['id'], $mail);
            } catch (MailingException|Exception $exception) {
                $output->writeln(sprintf(
                    'Mail #%s could not be (re-)send due to error: %s',
                    $mailRow['id'],
                    $exception->getMessage()
                ));
                $status = 1;
            }

            $this->mailRepository->increaseMailTries($mailRow['id'], $mailRow['tries']);
        }

        $this->mailRepository->cleanupOldData();

        return $status;
    }
}
