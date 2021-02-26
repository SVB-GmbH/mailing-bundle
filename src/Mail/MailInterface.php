<?php
namespace SVB\Mailing\Mail;

use SVB\Mailing\Exception\MailingException;

interface MailInterface
{
    public function __construct(string $recipient, array $data);

    public static function getIdentifier(): string;

    public static function getTemplateId(): int;

    public static function getConnector(): string;

    /** @throws MailingException */
    public function valid(): bool;

    public function getRecipient(): string;

    public function getData(): array;
}
