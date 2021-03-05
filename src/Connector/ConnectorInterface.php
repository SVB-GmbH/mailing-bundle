<?php
namespace SVB\Mailing\Connector;

use SVB\Mailing\Exception\MailingException;
use SVB\Mailing\Mail\MailInterface;

interface ConnectorInterface
{
    public const MAIL_STATUS_SUCCESS = 'success';
    public const MAIL_STATUS_TRY_LATER = 'try_later';
    public const MAIL_STATUS_FAILED = 'failed';

    public static function getAlias(): string;

    /**
     * @throws MailingException
     * @return string identifier to fetch information about the message from the api
     */
    public function sendMail(MailInterface $mail): string;

    /**
     * @param string $identifier the identifier used to identify
     * @return string one of the "ConnectorInterface::STATUS_" constants
     */
    public function getMailStatus(string $identifier): string;
}
