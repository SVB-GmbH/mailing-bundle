<?php
namespace SVB\Mailing;

use SVB\Mailing\Connector\ConnectorInterface;
use SVB\Mailing\Exception\MailingException;
use SVB\Mailing\Mail\MailInterface;

class Mailer
{
    private $connectors = [];

    public function addConnector(ConnectorInterface $connector)
    {
        $this->connectors[get_class($connector)] = $connector;
    }

    /**
     * @throws MailingException
     */
    public function sendMail(MailInterface $mail): string
    {
        if (!$mail->valid()) {
            throw new MailingException('Mail data validation failed, no error specified!');
        }

        return $this->getConnector($mail::getConnector())->sendMail($mail);
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
