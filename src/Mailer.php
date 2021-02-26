<?php
namespace SVB\Mailing;

use SVB\Mailing\Connector\ConnectorInterface;
use SVB\Mailing\Exception\MailingException;
use SVB\Mailing\Mail\MailInterface;

class Mailer
{
    /** @var ConnectorInterface[] */
    private $connectors = [];

    /** @var MailRepository */
    private $mailRepository;

    public function addConnector(ConnectorInterface $connector)
    {
        $this->connectors[get_class($connector)] = $connector;
    }

    public function setMailRepository(MailRepository $mailRepository)
    {
        $this->mailRepository = $mailRepository;
    }

    /**
     * @param MailInterface $mail the mail dto that should be send
     * @param bool $spool if set to true, the message is spooled and will be send asynchronous, which is faster
     * @throws MailingException
     */
    public function sendMail(MailInterface $mail, bool $spool = false): void
    {
        if (!$mail->valid()) {
            throw new MailingException('Mail data validation failed, no error specified!');
        }

        $connector = $this->getConnector($mail::getConnector());

        if (!$spool) {
            try {
                $messageIdentifier = $connector->sendMail($mail);
                if (!empty($messageIdentifier)) {
                    $this->mailRepository->logMail($mail, $messageIdentifier);
                    return;
                }
            } catch (MailingException $exception) {
                // TODO log exception somewhere
                $a = true;
            }
        }

        $this->mailRepository->logMail($mail);
    }

    /**
     * @param int $mailId database mail id
     * @param MailInterface $mail the mail dto that should be send
     * @throws MailingException
     */
    public function resendMail(int $mailId, MailInterface $mail): void
    {
        $connector = $this->getConnector($mail::getConnector());
        try {
            $messageIdentifier = $connector->sendMail($mail);
            if (!empty($messageIdentifier)) {
                $this->mailRepository->updateMailApiIdentifier($mailId, $messageIdentifier);
                return;
            }
        } catch (MailingException $exception) {
            // TODO log exception somewhere
            $a = true;
        }
    }

    /**
     * @throws MailingException
     */
    public function getMailStatus(MailInterface $mail, string $identifier): bool
    {
        return $this->getConnector($mail::getConnector())->getMailStatus($identifier);
    }

    /**
     * @throws MailingException
     */
    private function getConnector(string $connectorClassName): ConnectorInterface
    {
        if (!array_key_exists($connectorClassName, $this->connectors)) {
            throw new MailingException(sprintf('Connector %s not found', $connectorClassName));
        }

        return $this->connectors[$connectorClassName];
    }
}
