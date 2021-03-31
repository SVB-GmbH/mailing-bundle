<?php
namespace SVB\Mailing\Mail;

use SVB\Mailing\Exception\MailDataInvalidException;

interface MailInterface
{
    public function __construct(string $recipient, array $data, string $locale);

    public static function getTemplateAlias(): string;

    public function getTemplateId(): string;

    public static function getConnector(): string;

    /** @throws MailDataInvalidException */
    public function valid(): bool;

    public function getRecipient(): string;

    public function getData(): array;

    public function getLocale(): string;

    public function getIdentifier(): string;
}
