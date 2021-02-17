<?php
namespace SVB\Mailing\Mail;

abstract class AbstractMail implements MailInterface
{
    /** @var string[] */
    private $recipients;

    /** @var array */
    private $data;

    public function __construct(array $recipients, array $data)
    {
        $this->recipients = $recipients;
        $this->data = $data;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
