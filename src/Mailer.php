<?php
namespace SVB\Mailing;

use Mailjet\Client;
use Mailjet\Resources;
use SVB\Mailing\Exception\MailingException;
use SVB\Mailing\Exception\MailjetException;
use SVB\Mailing\Mail\MailInterface;

class Mailer
{
    /** @var Client */
    private $mailjetClient;

    public function setClient(Client $mailjetClient): Mailer
    {
        $this->mailjetClient = $mailjetClient;

        return $this;
    }

    /**
     * @param MailInterface[] $mails
     * @throws MailingException
     */
    public function sendMails(array $mails): bool
    {
        $response = $this->mailjetClient->post(Resources::$Email, ['body' => [
            'Messages' => array_map(function(MailInterface $mail) {
                return [
                    'To' => array_map(
                        function ($email) {
                            return [
                                'Email' => $email,
                            ];
                        },
                        $mail->getRecipients()
                    ),
                    'TemplateID' => $mail::getTemplateId(),
                    'TemplateLanguage' => true,
                    'Variables' => $mail->getData(),
                ];
            }, $mails),
        ]]);

        if (!$response->success()) {
            throw new MailjetException($response->getReasonPhrase() ?? 'no reason specified by mailjet');
        }

        return true;
    }
}
