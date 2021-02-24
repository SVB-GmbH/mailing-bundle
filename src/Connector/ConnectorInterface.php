<?php
namespace SVB\Mailing\Connector;

use SVB\Mailing\Exception\MailingException;
use SVB\Mailing\Mail\MailInterface;

interface ConnectorInterface
{
    public static function getAlias(): string;

    /**
     * @throws MailingException
     * @return string identifier to fetch information about the message from the api
     */
    public function sendMail(MailInterface $mail): string;

    /**
     * @param string $identifier the identifier used to identify
     * @return bool
     */
    public function getMailStatus(string $identifier): bool;
}
