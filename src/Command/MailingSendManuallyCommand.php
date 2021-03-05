<?php

namespace SVB\Mailing\Command;

use InvalidArgumentException;
use SVB\Mailing\Mail\MailInterface;
use SVB\Mailing\Mailer;
use SVB\Mailing\Repository\MailRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MailingSendManuallyCommand extends Command
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
            ->setName('svb:mailing:send-manually')
            ->setDescription('Send a mail manually using the console')
            ->addArgument('mail_identifier', InputArgument::REQUIRED, 'The mail template identifier (see --mails for all available identifiers).')
            ->addArgument('receiver', InputArgument::REQUIRED, 'The email address for the receiver of the mail.')
            ->addArgument('data', InputArgument::REQUIRED, 'Json valid string containing all necessary mail data.')
            ->addArgument('locale', InputArgument::OPTIONAL, 'Locale to set the language of the template.')
            ->addOption('mails', 'm', InputOption::VALUE_NONE, 'Display all mail templates available.')
            ->addOption('spool', 's', InputOption::VALUE_NONE, 'Set this option to spool the mail and send it later.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('mails')) {
            $output->writeln('All available mail templates:');
            foreach ($this->mailRepository->getMailIdentifierMap() as $identifier => $class) {
                $output->writeln($identifier . '(' . $class . ')');
            }

            return 0;
        }

        $mail = $this->mailRepository->constructMailObject(
            $input->getArgument('mail_identifier'),
            $input->getArgument('data'),
            $input->getArgument('receiver'),
            $input->getArgument('locale')
        );

        if (!$mail instanceof MailInterface) {
            throw new InvalidArgumentException('Mail could not be initialized. Maybe the mail identifier is not available? Execute with --mails to see a list of available mail templates.');
        }

        $this->mailer->sendMail($mail, $input->getOption('mails') ? true : false);

        $output->writeln('Mail successfully sent to ' . $mail->getRecipient());

        return 0;
    }
}
