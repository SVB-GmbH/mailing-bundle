<?php
namespace SVB\Mailing\Mail;

abstract class AbstractMail implements MailInterface
{
    /** @var string */
    protected $recipient;

    /** @var array */
    protected $data;

    /** @var string */
    protected $locale;

    /** @var string */
    protected $identifier;

    public function __construct(string $recipient, array $data, string $locale, string $identifier = '')
    {
        $this->recipient = $recipient;
        $this->data = $data;
        $this->locale = $locale;
        $this->identifier = $identifier;
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

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
