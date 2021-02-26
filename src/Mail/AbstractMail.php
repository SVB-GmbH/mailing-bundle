<?php
namespace SVB\Mailing\Mail;

abstract class AbstractMail implements MailInterface
{
    /** @var string */
    private $recipient;

    /** @var array */
    private $data;

    public function __construct(string $recipient, array $data)
    {
        $this->recipient = $recipient;
        $this->data = $data;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function valid(): bool
    {
        return true;
    }
}
