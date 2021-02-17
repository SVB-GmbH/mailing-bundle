<?php
namespace SVB\Mailing\Mail;

interface MailInterface
{
    public static function getTemplateId(): int;

    public function valid(): bool;

    /** @return string[] */
    public function getRecipients(): array;

    public function getData(): array;
}
