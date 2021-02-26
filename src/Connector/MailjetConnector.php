<?php

namespace SVB\Mailing\Connector;

use GuzzleHttp\Exception\GuzzleException;
use Mailjet\Client;
use Mailjet\Resources;
use SVB\Mailing\Exception\MailjetException;
use SVB\Mailing\Mail\MailInterface;

class MailjetConnector implements ConnectorInterface
{
    public const CONNECTOR_ALIAS = 'mailjet';

    /** @var Client|null */
    private $mailjetNewClient = null;

    /** @var Client|null */
    private $mailjetOldClient = null;

    public static function getAlias(): string
    {
        return self::CONNECTOR_ALIAS;
    }

    /**
     * @throws MailjetException
     */
    public function sendMail(MailInterface $mail): string
    {
        // todo check if mailjet client has been set by DI
        try {
            $sendResponse = $this->mailjetNewClient->post(Resources::$Email, ['body' => [
                'Messages' => [
                    [
                        'To' => [
                            [
                                'Email' => $mail->getRecipient()
                            ],
                        ],
                        'TemplateID' => $mail::getTemplateId(),
                        'TemplateLanguage' => true,
                        'Variables' => $mail->getData(),
                        'AdvanceErrorHandling' => true,
                        'Globals' => [], # TODO implement globals logic
                        'TemplateErrorReporting' => [
                            'Email' => 'psi@svb.de',
                            'Name' => 'Patrick Siemen',
                        ],
                    ],
                ],
            ]]);
        } catch (GuzzleException $exception) {
            throw new MailjetException('Mailjet API connection failed', 0, $exception);
        }

        if (!$sendResponse->success()) {
            throw new MailjetException($sendResponse->getReasonPhrase() ?? 'no reason specified by mailjet');
        }

        $messageId = $sendResponse->getBody()['Messages'][0]['To'][0]['MessageID'] ?? null;

        if (!is_numeric($messageId)) {
            throw new MailjetException('Mailjet does not return message id.');
        }

        return (string) $messageId;
    }

    /**
     * todo implement way to catch things like blacklisted and set the mail directly to failed instead of trying multiple times
     */
    public function getMailStatus(string $identifier): bool
    {
        $response = $this->mailjetOldClient->get(Resources::$Message, ['id' => $identifier]);

        if (null === $response->getBody()['Data'][0]['StateID'] ?? null) {
            return true;
        }

        return false;
    }

    public function setNewClient(Client $mailjetClient): MailjetConnector
    {
        $this->mailjetNewClient = $mailjetClient;

        return $this;
    }

    public function setOldClient(Client $mailjetClient): MailjetConnector
    {
        $this->mailjetOldClient = $mailjetClient;

        return $this;
    }
}
